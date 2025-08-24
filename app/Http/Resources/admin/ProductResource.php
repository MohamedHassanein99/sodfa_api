<?php

namespace App\Http\Resources\admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'category_id' => $this->category_id,
            'is_featured' => $this->is_featured,
            'created_at' => $this->created_at,
            'colors' => $this->productColorImages->map(function($colorImage) {
                return [
                    'color_id' => $colorImage->color_id,
                    'color_name' => $colorImage->color->name ?? null,
                    'image_url' => $colorImage->image ? asset('storage/' . $colorImage->image) : null,
                ];
            }),
        ];
    }
}
