<?php

namespace App\Http\Resources\user;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'price'      => $this->price,
            'category_id'=> $this->category_id,
            'image_url'  => $this->image ? asset('storage/' . $this->image) : null,
            'created_at'  => $this->created_at,
            'sales_count'=> $this->when(isset($this->order_items_sum_quantity), $this->order_items_sum_quantity),
        ];
    }
}
