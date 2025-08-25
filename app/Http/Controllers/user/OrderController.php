<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Http\Resources\user\OrderResource;
use Illuminate\Http\Request;
use App\Models\user\Order;
use App\Models\user\OrderItem;
use App\Models\user\Cart;
use App\Models\user\PaymentMethod;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::where('user_id', auth()->id())
            ->with('orderItems.product', 'paymentMethod')
            ->get();

        return response()->json([
            'orders' => OrderResource::collection($orders)
        ]);
    }

    public function show(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order->load('orderItems.product', 'paymentMethod');

        return response()->json([
            'order' => new OrderResource($order)
        ]);
    }

    public function store(OrderRequest $request)
    {
        $cart = Cart::where('user_id', auth()->id())
            ->where('status', 'processing')
            ->with('cartItems.product')
            ->first();

        if (!$cart || $cart->cartItems->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 422);
        }

        DB::beginTransaction();

        try {
            $subtotal = $cart->cartItems->sum(fn($item) =>
                $item->quantity * $item->product->price
            );

            $order = Order::create([
                'user_id' => auth()->id(),
                'name' => $request->name,
                'phone' => $request->phone,
                'city' => $request->city,
                'email' => $request->email,
                'address' => $request->address,
                'total_price' => $subtotal,
                'status' => 'processing'
            ]);

            if ($request->hasFile('receipt_image')) {
                $receiptPath = $request->file('receipt_image')->store('receipts', 'public');
            }

            PaymentMethod::create([
                'order_id' => $order->id,
                'payment_method' => $request->payment_method,
                'receipt_image' => $receiptPath ?? null,
                'status' => 'processing',
            ]);

            foreach ($cart->cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'subtotal' => $item->quantity * $item->product->price,
                ]);
            }

            $cart->status = 'closed';
            $cart->save();

            DB::commit();

            $order->load('orderItems.product', 'paymentMethod');

            return response()->json([
                'order' => new OrderResource($order),
                'message' => 'Order created successfully',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancel(Request $request, Order $order)
{
    if ($order->user_id !== auth()->id()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    if (!in_array($order->status, ['processing'])) {
        return response()->json([
            'message' => 'Order cannot be canceled at this stage.'
        ], 422);
    }

    $request->validate([
        'cancellation_reason' => 'required|string|max:255',
    ]);

    $order->update([
        'status' => 'canceled',
        'cancellation_reason' => $request->cancellation_reason,
    ]);

    return response()->json([
        'message' => 'Order canceled successfully',
        'order'   => new OrderResource($order)
    ]);
}

}
