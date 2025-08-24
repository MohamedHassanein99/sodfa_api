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

        if ($request->has('status') && in_array($request->status, ['pending', 'processing', 'delivered'])) {
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
            'pending' => Order::where('status', 'pending')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
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
            'status' => 'required|string|in:pending,processing,delivered,canceled',
        ]);

        $order->update($request->only('status'));

        return response()->json([
            'order' => new OrderResource($order->load('orderItems.product', 'paymentMethod')),
            'message' => 'Order updated successfully by admin'
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
