<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Resources\user\FavoriteResource;
use App\Models\products\Product;
use Illuminate\Http\Request;
use App\Models\user\Favorite;
use App\Models\User;

class FavoriteController extends Controller

{
    public function index()
    {
        $favorites = Favorite::where('user_id', auth()->id())
            ->with('product')
            ->get();

        return response()->json([
            'favorites' => FavoriteResource::collection($favorites),
            'message' => $favorites->isEmpty() ? 'No favorites found' : 'Favorites found',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $favorite = Favorite::firstOrCreate([
            'user_id' => auth()->id(),
            'product_id' => $request->product_id,
        ]);

        return response()->json([
            'favorite' => new FavoriteResource($favorite->load('product')),
            'message' => 'Product added to favorites',
        ]);
    }

    public function destroy(Favorite $favorite)
    {
        if ($favorite->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $favorite->delete();

        return response()->json([
            'message' => 'Favorite deleted successfully',
        ]);
    }

    public function clear()
    {
        $deleted = Favorite::where('user_id', auth()->id())->delete();

        if ($deleted === 0) {
            return response()->json([
                'message' => 'No favorites found',
            ], 404);
        }

        return response()->json([
            'message' => 'Favorites cleared successfully',
        ]);
    }
}
