<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class ShopController extends Controller
{
    public function getHomeCategories(Request $request)
    {
        $categoriesLimit = $request->query('limit', 3);
        // تحويل الـ sort لـ lowercase لضمان التطابق التام مع الفرونت إند
        $sort = strtolower($request->query('sort', 'newest'));

        $isPriceSort = false;
        $sortDirection = $sort === 'price-min' ? 'asc' : 'desc';

        if ($sort === 'price-min' || $sort === 'price-max') {
            $isPriceSort = true;
        }

        // جلب الأقسام وتطبيق الترتيب الديناميكي بعد الخصم على المنتجات اللي جواها 🎯
        $categories = Category::with(['products' => function ($q) use ($isPriceSort, $sortDirection) {
            if ($isPriceSort) {
                // 🎯 الحسبة السحرية: تنظيف السعر والخصم، وطرح الخصم لو موجود، ثم الترتيب بناءً على الناتج
                $q->orderByRaw("
                (
                    CAST(REGEXP_REPLACE(price, '[^0-9.]', '') AS DECIMAL(10,2)) -
                    COALESCE(CAST(REGEXP_REPLACE(discount, '[^0-9.]', '') AS DECIMAL(10,2)), 0)
                ) " . $sortDirection);
            } else {
                // الترتيب حسب الأحدث
                $q->orderBy('created_at', 'desc');
            }
            $q->with('Images');
        }])
            ->take((int)$categoriesLimit)
            ->get();

        // تحديد أول 10 منتجات وأول صورة فقط لكل منتج من أجل الأداء
        $categories->each(function ($category) {
            $limitedProducts = $category->products->take(10);
            $limitedProducts->each(function ($product) {
                if ($product->Images && $product->Images->isNotEmpty()) {
                    $product->setRelation('Images', collect([$product->Images->first()]));
                }
            });
            $category->setRelation('products', $limitedProducts);
        });

        $totalCategoriesCount = Category::count();

        return response()->json([
            'status' => 'success',
            'total_categories' => $totalCategoriesCount,
            'data' => $categories
        ], 200);
    }

    public function search(Request $request)
    {
        $search = $request->query('search');
        // تحويل الـ sort لـ lowercase لضمان التطابق التام
        $sort = strtolower($request->query('sort', 'newest'));
        $categoriesLimit = $request->query('limit', 10);

        if (empty($search)) {
            return response()->json([
                'status' => 'success',
                'total_categories' => 0,
                'data' => []
            ], 200);
        }

        $isPriceSort = false;
        $sortDirection = $sort === 'price-min' ? 'asc' : 'desc';

        if ($sort === 'price-min' || $sort === 'price-max') {
            $isPriceSort = true;
        }

        $categories = Category::whereHas('products', function ($q) use ($search) {
            $q->where('title', 'LIKE', '%' . $search . '%');
        })
            ->with(['products' => function ($q) use ($isPriceSort, $sortDirection, $search) {
                // فلترة المنتجات جوه الفئة حسب العنوان فقط
                $q->where('title', 'LIKE', '%' . $search . '%');

                // تطبيق الترتيب الديناميكي (حسب السعر بعد الخصم أو الأحدث) 🎯
                if ($isPriceSort) {
                    // الحسبة السحرية: تنظيف السعر والخصم وطرح الخصم لو موجود
                    $q->orderByRaw("
                    (
                        CAST(REGEXP_REPLACE(price, '[^0-9.]', '') AS DECIMAL(10,2)) -
                        COALESCE(CAST(REGEXP_REPLACE(discount, '[^0-9.]', '') AS DECIMAL(10,2)), 0)
                    ) " . $sortDirection);
                } else {
                    $q->orderBy('created_at', 'desc');
                }
                $q->with('Images');
            }])
            ->take((int)$categoriesLimit)
            ->get();

        // فك وتحديد أول صورة لكل منتج لسرعة التحميل
        $categories->each(function ($category) {
            $limitedProducts = $category->products->take(10);
            $limitedProducts->each(function ($product) {
                if ($product->Images && $product->Images->isNotEmpty()) {
                    $product->setRelation('Images', collect([$product->Images->first()]));
                }
            });
            $category->setRelation('products', $limitedProducts);
        });

        return response()->json([
            'status' => 'success',
            'total_categories' => $categories->count(),
            'data' => $categories
        ], 200);
    }
}
