<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TranslateAttributes;

class Review extends Model
{
    use TranslateAttributes;
    use HasFactory;

    protected $fillable = ['product_id', 'user_id', 'comment', 'rating'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
