<?php

namespace App\Models\products;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Color extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'hex_code',
    ];
    public function productColorImage()
    {
        return $this->hasMany(ProductColorImage::class);
    }
}
