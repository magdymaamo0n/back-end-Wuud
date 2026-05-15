<?php

namespace App\Traits;

trait HasFullImageUrl
{

    public function getImageAttribute($value)
    {
        if (!$value) {
            return asset('images/default.jpg');
        }

        $cleanPath = ltrim($value, '/');

        return asset($cleanPath);
    }
}
