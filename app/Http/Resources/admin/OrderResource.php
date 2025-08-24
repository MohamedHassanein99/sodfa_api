<?php

namespace App\Http\Resources\admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'city' => $this->city,
            'email' => $this->email,
            'address' => $this->address,
            'total_price' => $this->total_price,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'payment_method' => $this->whenLoaded('paymentMethod', function () {
                return [
                    'method' => $this->paymentMethod->payment_method ?? null,
                    'status' => $this->paymentMethod->status ?? null,
                    'receipt_image' => $this->paymentMethod->receipt_image ?? null,
                ];
            }),
            'items' => $this->whenLoaded('orderItems', function () {
                return $this->orderItems->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'name' => $item->product->name ?? null,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => $item->subtotal,
                    ];
                });
            }),
        ];
    }
}
