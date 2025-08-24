<?php

namespace App\Http\Resources\user;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'user_id'       => $this->user_id,
            'name'          => $this->name,
            'phone'         => $this->phone,
            'email'         => $this->email,
            'city'          => $this->city,
            'address'       => $this->address,
            'status'        => $this->status,
            'total_price'   => $this->total_price,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
            'items'         => OrderItemResource::collection($this->orderItems),
            'payment_method'=> [
                'id'             => $this->paymentMethod->id ?? null,
                'payment_method' => $this->paymentMethod->payment_method ?? null,
                'receipt_image'  => $this->paymentMethod?->receipt_image
                                    ? asset('storage/' . $this->paymentMethod->receipt_image)
                                    : null,
                'status'         => $this->paymentMethod->status ?? null,
            ],
        ];
    }
}
