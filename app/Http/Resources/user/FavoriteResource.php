<?php

namespace App\Http\Resources\user;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class FavoriteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $createdAt = $this->created_at;
        if ($createdAt instanceof \Carbon\Carbon) {
            $createdAt = $createdAt->toDateTimeString();
        } else {
            $createdAt = (string) $createdAt;
        }

        $updatedAt = $this->updated_at;
        if ($updatedAt instanceof \Carbon\Carbon) {
            $updatedAt = $updatedAt->toDateTimeString();
        } else {
            $updatedAt = (string) $updatedAt;
        }

        return [
            'id'      => $this->id,
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id'    => $this->product->id,
                    'name'  => $this->product->name,
                    'price' => $this->product->price,
                    'image_url' => $this->product->image
                        ? asset('storage/' . $this->product->image)
                        : null,
                ];
            }),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }
}
