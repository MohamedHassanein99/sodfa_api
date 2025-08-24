<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\admin\ProductResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Admin\ProductRequest;
use Illuminate\Http\Request;
use App\Models\products\Product;
use App\Models\products\OrderItem;
use App\Models\products\Color;
use App\Models\products\ProductColorImage;
use Illuminate\Support\Facades\DB;


class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();


        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('sort_by')) {
            switch ($request->sort_by) {
                case 'name':
                    $query->orderBy('name', 'asc');
                    break;
                case 'price':
                    $query->orderBy('price', 'asc');
                    break;
                case 'created_at':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'sales':
                    $query->withSum('order_items', 'quantity')
                        ->orderBy('order_items_sum_quantity', 'desc');
                    break;
            }
        }

        $totalProducts = Product::count();
        $totalFeaturedProduct = Product::where('is_featured', true)->count();
        $watchProductCount = Product::where('category_id', 1)->count();
        $accessoryProductsCount = Product::where('category_id', 2)->count();

        return response()->json([
            'stats' => [
                'totalProducts' => $totalProducts,
                'totalFeaturedProduct' => $totalFeaturedProduct,
                'watchProductCount' => $watchProductCount,
                'accessoryProductsCount' => $accessoryProductsCount,
            ],
            'products' => ProductResource::collection($query->paginate(12)),
        ]);
    }

    public function store(ProductRequest $request)
    {
        DB::beginTransaction();
        try {
            $product = Product::create($request->validated());

            if (!$request->has('colors') || count($request->colors) < 1) {
                return response()->json([
                    'message' => 'Colors are required',
                ], 422);
            }

            foreach ($request->colors as $index => $colorData) {
                $path = null;

                if ($request->hasFile("colors.$index.image")) {
                    $path = $request->file("colors.$index.image")->store('products', 'public');
                }

                ProductColorImage::create([
                    'product_id' => $product->id,
                    'color_id'   => $colorData['color_id'],
                    'image' => $path,
                ]);
            }

            DB::commit();

            $product->load(['productColorImages.color']);

            foreach ($product->productColorImages as $pi) {
                if ($pi->image) {
                    $pi->image_url = asset('storage/' . $pi->image);
                }
            }

            return response()->json([
                'message' => 'Product created/updated successfully',
                'product' => new ProductResource($product),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $product = Product::with(['productColorImages.color'])->findOrFail($id);
        return new ProductResource($product);
    }

    public function update(ProductRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $product = Product::findOrFail($id);
            $product->update($request->validated());

            if (!$request->has('colors') || count($request->colors) < 1) {
                return response()->json([
                    'message' => 'Colors are required',
                ], 422);
            }

            $productColorIds = collect($request->colors)->pluck('color_id')->toArray();
            $product->productColorImages()
                ->whereNotIn('color_id', $productColorIds)
                ->each(function ($colorImage) {
                    if ($colorImage->image && Storage::disk('public')->exists($colorImage->image)) {
                        Storage::disk('public')->delete($colorImage->image);
                    }
                    $colorImage->delete();
                });

            foreach ($request->colors as $index => $colorData) {
                $productColor = ProductColorImage::firstOrNew([
                    'product_id' => $product->id,
                    'color_id' => $colorData['color_id'],
                ]);

                if ($request->hasFile("colors.$index.image")) {
                    $productColor->image = $request->file("colors.$index.image")->store('products', 'public');
                }

                $productColor->save();
            }

            DB::commit();

            $product->load(['productColorImages.color']);

            foreach ($product->productColorImages as $pi) {
                if ($pi->image) {
                    $pi->image_url = asset('storage/' . $pi->image);
                }
            }

            return response()->json([
                'message' => 'Product created/updated successfully',
                'product' => new ProductResource($product),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $product = Product::findOrFail($id);
            foreach ($product->productColorImages as $colorImage) {
                if ($colorImage->image && Storage::disk('public')->exists($colorImage->image)) {
                    Storage::disk('public')->delete($colorImage->image);
                }
            }
            $product->productColorImages()->delete();
            $product->delete();
            DB::commit();
            return response()->json([
                'message' => 'Product deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
