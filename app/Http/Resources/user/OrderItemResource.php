<?php

namespace App\Http\Resources\user;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'product_id'=> $this->product_id,
            'quantity'  => $this->quantity,
            'price'     => $this->price,
            'subtotal'  => $this->subtotal,
            'product'   => [
                'id'    => $this->product->id,
                'name'  => $this->product->name,
                'price' => $this->product->price,
                'image' => $this->product->image ? asset('storage/' . $this->product->image) : null,
            ]
        ];
    }
}
