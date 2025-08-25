<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\user\Cart;
use App\Models\user\CartItem;
use App\Models\products\Product;
use App\Http\Resources\user\CartResource;

class CartController extends Controller
{
    public function index()
    {
        $cart = Cart::where('user_id', auth()->id())
            ->where('status', 'processing')
            ->with('cartItems.product')
            ->first();

        if (!$cart) {
            return response()->json([
                'cart' => null,
                'message' => 'Cart not found'
            ]);
        }

        return response()->json([
            'cart' => new CartResource($cart),
            'message' => 'Cart found'
        ]);
    }

    public function store(Request $request)
{
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'quantity' => 'required|integer|min:1',
    ]);

    $product = Product::findOrFail($request->product_id);

    $cart = User::find(auth()->id())->carts()->firstOrCreate(
        ['status' => 'processing'],
        [
            'price' => 0,
            'expires_at' => now()->addDays(3),
        ]
    );

    $cartItem = $cart->cartItems()->where('product_id', $request->product_id)->first();

    if ($cartItem) {
        $cartItem->quantity += $request->quantity;
        $cartItem->price = $cartItem->quantity * $product->price;
        $cartItem->save();
    } else {
        $cart->cartItems()->create([
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'price' => $request->quantity * $product->price,
        ]);
    }

    $cart->price = $cart->cartItems()->sum('price');

    if (!$cart->expires_at || $cart->expires_at < now()) {
        $cart->expires_at = now()->addDays(3);
    }

    $cart->save();

    return response()->json([
        'cart' => new CartResource($cart->fresh('cartItems.product')),
        'message' => 'Product added to cart'
    ]);
}


public function update(Request $request, CartItem $cartItem)
{
    $request->validate([
        'quantity' => 'required|integer|min:1',
    ]);

    if ($cartItem->cart->user_id !== auth()->id()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $cartItem->quantity = $request->quantity;
    $cartItem->price = $cartItem->quantity * $cartItem->product->price;
    $cartItem->save();

    $cart = $cartItem->cart;
    $cart->price = $cart->cartItems()->sum('price');

    if (!$cart->expires_at || $cart->expires_at < now()) {
        $cart->expires_at = now()->addDays(3);
    }

    $cart->save();

    return response()->json([
        'cart' => new CartResource($cart->fresh('cartItems.product')),
        'message' => 'Cart item updated'
    ]);
}

public function destroy(CartItem $cartItem)
{
    if ($cartItem->cart->user_id !== auth()->id()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $cart = $cartItem->cart;
    $cartItem->delete();

    $cart->price = $cart->cartItems()->sum('price');

    if ($cart->cartItems()->count() > 0) {
        $cart->expires_at = $cart->expires_at ?? now()->addDays(3);
    } else {
        $cart->expires_at = null;
    }

    $cart->save();

    return response()->json([
        'cart' => new CartResource($cart->fresh('cartItems.product')),
        'message' => 'Cart item deleted'
    ]);
}

public function clear()
{
    $cart = User::find(auth()->id())->carts()->where('status', 'processing')->first();

    if (!$cart) {
        return response()->json([
            'cart' => null,
            'message' => 'Cart not found'
        ]);
    }

    $cart->cartItems()->delete();
    $cart->price = 0;
    $cart->expires_at = null;
    $cart->save();

    return response()->json([
        'cart' => new CartResource($cart->fresh('cartItems.product')),
        'message' => 'Cart cleared'
    ]);
}

}
