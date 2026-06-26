<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allCategories = Category::all();
        $categories = Category::paginate($request->input('limit', 10));
        $finalResult = $request->input('limit') ? $categories : $allCategories;
        return $finalResult;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $category = new Category();
        $request->validate([
            'title' => 'required',
            'image' => 'required|image'
        ]);
        $category->title = $request->title;
        if ($request->hasFile('image')) {
            $file = $request->file('image');

            // Store the image in the 'public/images' directory
            $path = $file->store('images', 'public');

            // Generate the public URL
            $category->image = Storage::url($path);
        }
        $category->save();
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category, $id)
    {
        return Category::findOrFail($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category, $id, Request $request)
    {
        $category = Category::findOrFail($id);
        $request->validate([
            'title' => 'required',
        ]);
        $category->title = $request->title;
        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($category->image) {
                $oldPath = str_replace(url('/storage'), 'public', $category->image);
                if (Storage::exists($oldPath)) {
                    Storage::delete($oldPath);
                }
            }
            // Store the new image
            $file = $request->file('image');
            $path = $file->store('images', 'public');
            $category->image = Storage::url($path); // Generate the public URL
        }

        $category->save();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        //
    }

    // Search On Users
    public function search(Request $request)
    {
        $date = $request->input('date');

        // بياخد كل الداتا ماعدا التاريخ
        $allData = $request->except('date');
        // بيجيب أول قيمة مبعوتة (اللي هي كلمة البحث)
        $searchWord = reset($allData);

        // لو الخانتين فاضيين رجع فاضي
        if (empty($searchWord) && empty($date)) {
            return response()->json([]);
        }

        $query = Category::query();

        // البحث بالـ title (تأكد إن اسم العمود في جدول الـ categories هو title)
        if (!empty($searchWord)) {
            $query->where('title', 'LIKE', '%' . $searchWord . '%');
        }

        if (!empty($date)) {
            $query->whereDate('created_at', $date);
        }

        $results = $query->get();

        return response()->json($results);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category, $id)
    {
        $category = Category::findOrFail($id);
        if ($category->image) {
            // Extract the relative storage path from the image URL
            $path = str_replace(url('/storage'), 'public', $category->image);

            // Check if the file exists and delete it
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
        }

        $category->delete();
    }

    public function getProducts(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['status' => 'error', 'message' => 'Category not found'], 404);
        }

        $limit = $request->query('limit', 10);
        $page = $request->query('page', 1);
        $offset = ($page - 1) * $limit;

        $sort = strtolower($request->query('sort', 'newest'));

        $foreignKey = $category->products()->getForeignKeyName();
        $productsQuery = Product::where($foreignKey, $id)->with('Images');

        if ($sort === 'price-min' || $sort === 'price-max') {
            $sortDirection = $sort === 'price-min' ? 'asc' : 'desc';

            // 🎯 الحسبة السحرية: تنظيف السعر والخصم، وطرح الخصم لو موجود، ثم الترتيب بناءً على الناتج
            $productsQuery->orderByRaw("
            (
                CAST(REGEXP_REPLACE(price, '[^0-9.]', '') AS DECIMAL(10,2)) -
                COALESCE(CAST(REGEXP_REPLACE(discount, '[^0-9.]', '') AS DECIMAL(10,2)), 0)
            ) " . $sortDirection);
        } else {
            $productsQuery->orderBy('created_at', 'desc');
        }

        $totalProductsCount = $productsQuery->count();

        $products = $productsQuery->skip((int)$offset)
            ->take((int)$limit)
            ->get();

        $products->each(function ($product) {
            if ($product->Images && $product->Images->isNotEmpty()) {
                $product->setRelation('Images', collect([$product->Images->first()]));
            }
        });

        return response()->json([
            'status' => 'success',
            'category_title' => $category->title,
            'total_products' => $totalProductsCount,
            'current_page' => (int)$page,
            'products' => $products
        ], 200);
    }

    public function searchProductsInCategory(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['status' => 'error', 'message' => 'Category not found'], 404);
        }

        $limit = $request->query('limit', 10);
        $page = $request->query('page', 1);
        $offset = ($page - 1) * $limit;

        $sort = strtolower($request->query('sort', 'newest'));
        $search = $request->query('search');

        $foreignKey = $category->products()->getForeignKeyName();
        $productsQuery = Product::where($foreignKey, $id)->with('Images');

        if (!empty($search)) {
            $productsQuery->where('title', 'LIKE', '%' . $search . '%');
        }

        if ($sort === 'price-min' || $sort === 'price-max') {
            $sortDirection = $sort === 'price-min' ? 'asc' : 'desc';

            $productsQuery->orderByRaw("
            (
                CAST(REGEXP_REPLACE(price, '[^0-9.]', '') AS DECIMAL(10,2)) -
                COALESCE(CAST(REGEXP_REPLACE(discount, '[^0-9.]', '') AS DECIMAL(10,2)), 0)
            ) " . $sortDirection);
        } else {
            $productsQuery->orderBy('created_at', 'desc');
        }

        $totalProductsCount = $productsQuery->count();

        $products = $productsQuery->skip((int)$offset)
            ->take((int)$limit)
            ->get();

        $products->each(function ($product) {
            if ($product->Images && $product->Images->isNotEmpty()) {
                $product->setRelation('Images', collect([$product->Images->first()]));
            }
        });

        return response()->json([
            'status' => 'success',
            'category_title' => $category->title,
            'total_products' => $totalProductsCount,
            'current_page' => (int)$page,
            'products' => $products
        ], 200);
    }
}
