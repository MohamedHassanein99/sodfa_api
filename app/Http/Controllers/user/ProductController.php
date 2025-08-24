<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Resources\user\ProductResource;
use App\Models\products\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function allProducts(Request $request)
    {
        $query = Product::with(['productColorImages.color']); 

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sorting
        if ($request->has('sort_by')) {
            switch ($request->sort_by) {
                case 'created_at':
                    $query->orderBy('created_at', 'desc');
                    break;

                case 'price':
                    $direction = $request->get('sort_price_direction', 'asc');
                    $query->orderBy('price', $direction);
                    break;

                case 'sales':
                    $query->withSum('orderItems', 'quantity')
                          ->orderBy('order_items_sum_quantity', 'desc');
                    break;

                case 'name':
                    $query->orderBy('name', 'asc');
                    break;
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate(12);

        return ProductResource::collection($products);
    }

    public function home()
    {
        $products = Product::with(['productColorImages.color'])
                           ->where('is_featured', true)
                           ->get();

        return ProductResource::collection($products);
    }
}
