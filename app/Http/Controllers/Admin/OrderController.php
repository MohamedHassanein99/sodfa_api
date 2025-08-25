<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\user\Order;
use App\Http\Resources\admin\OrderResource;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('orderItems.product', 'paymentMethod');

        if ($request->has('status') && in_array($request->status, ['processing', 'shipped', 'delivered', 'canceled'])) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $orders = $query->get();

        $stats = [
            'total' => Order::count(),
            'processing' => Order::where('status', 'processing')->count(),
            'shipped' => Order::where('status', 'shipped')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
            'canceled' => Order::where('status', 'canceled')->count(),
        ];

        return response()->json([
            'orders' => OrderResource::collection($orders),
            'stats' => $stats
        ]);
    }

    public function update(Request $request, Order $order)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|string|in:processing,shipped,delivered,canceled',
            'cancellation_reason' => 'nullable|string|max:500'
        ]);

        $newStatus = $request->status;

        if ($newStatus === 'canceled') {
            if ($order->status !== 'processing') {
                return response()->json([
                    'message' => 'Orders can only be canceled if they are in processing status.'
                ], 422);
            }

            if (empty($request->cancellation_reason)) {
                return response()->json([
                    'message' => 'Cancellation reason is required when canceling an order.'
                ], 422);
            }

            $order->update([
                'status' => 'canceled',
                'cancellation_reason' => $request->cancellation_reason
            ]);
        } else {
            $order->update([
                'status' => $newStatus,
                'cancellation_reason' => null
            ]);
        }

        return response()->json([
            'order' => new OrderResource($order->load('orderItems.product', 'paymentMethod')),
            'message' => "Order updated successfully by admin"
        ]);
    }




    public function destroy(Order $order)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully by admin'
        ]);
    }
}
