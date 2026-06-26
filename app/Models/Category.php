<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasFullImageUrl;
use App\Models\Product;
use App\Traits\TranslateAttributes;

class Category extends Model
{
    use TranslateAttributes;
    use HasFullImageUrl;
    use HasFactory;

    protected $fillable = ['title', 'image'];

    public function products()
    {
        return $this->hasMany(Product::class, 'category');
    }
}
