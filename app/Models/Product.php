<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TranslateAttributes;

class Product extends Model
{
    use TranslateAttributes;
    use HasFactory;
    protected $fillable = ['category', 'title', 'description', 'About', 'price', 'discount', 'stock'];

    public function Category()
    {
        return $this->belongsTo(Category::class);
    }


    public function Images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function Cart()
    {
        return $this->hasMany(Cart::class);
    }

    protected $appends = ['is_favorite', "rating"];

    public function getIsFavoriteAttribute()
    {
        $user = auth()->guard('api')->user();

        if (!$user) {
            return false;
        }

        // فحص في جدول الـ favorites
        return \DB::table('favorites')
            ->where('product_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function favorites()
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function getRatingAttribute()
    {
        $avg = $this->reviews()->avg('rating');

        return $avg ? round($avg, 1) : 0;
    }
}
