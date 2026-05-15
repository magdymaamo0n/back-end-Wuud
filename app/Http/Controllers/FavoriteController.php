<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    // لازم الفنكشن تكون جوه القوسين بتوع الكلاس
    public function toggleFavorite($product_id)
    {
        // بنستخدم guard('api') عشان نضمن إنه يكلم Passport
        $user = auth()->guard('api')->user();

        if (!$user) {
            return response()->json(['error' => 'Passport not recognizing token'], 401);
        }

        $exists = $user->favorites()->where('product_id', $product_id)->exists();

        if ($exists) {
            $user->favorites()->detach($product_id);
            $is_fav = false;
        } else {
            $user->favorites()->attach($product_id);
            $is_fav = true;
        }

        return response()->json([
            'status' => 'success',
            'is_favorite' => $is_fav
        ]);
    }

    public function index()
    {
        $user = auth()->guard('api')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // جلب المفضلات مع علاقة الصور (تأكد من كتابة Images كما في الموديل)
        $favorites = $user->favorites()->with('Images')->get();

        return response()->json([
            'status' => 'success',
            'data' => $favorites
        ]);
    }
}
