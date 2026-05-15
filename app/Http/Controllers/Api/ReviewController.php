<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'user_id'    => 'required',
            'comment'    => 'required',
            'rating'     => 'required|numeric|min:1|max:5', // تأكد إنه رقم
        ]);

        $review = Review::create($validated);

        $productId = $validated['product_id'];

        $average = Review::where('product_id', $productId)->avg('rating');

        Product::where('id', $productId)->update([
            'rating' => round($average, 1)
        ]);

        return response()->json([
            'message' => 'Review added successfully!',
            'review'  => $review,
            'new_rating' => round($average, 1)
        ], 201);
    }

    // جلب تعليقات منتج معين
    public function getProductReviews($product_id)
    {
        $reviews = Review::with(['user' => function ($query) {
            $query->select('id', 'name', 'avatar'); // بنحدد الأعمدة اللي عايزينها بس
        }])
            ->where('product_id', $product_id)
            ->latest()
            ->take(5)
            ->get();

        return response()->json($reviews);
    }

    public function getLatestReviews()
    {
        $reviews = Review::with(['user' => function ($query) {
            $query->select('id', 'name', 'avatar');
        }])
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return response()->json($reviews);
    }
}
