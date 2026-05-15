<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasFullImageUrl;

class ProductImage extends Model
{
    use HasFactory;
    use HasFullImageUrl;

    public function Product()
    {
        return $this->belongsTo(Product::class)->onDelete('cascade');
    }
}
