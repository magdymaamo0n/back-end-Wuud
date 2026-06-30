<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with(['Images', 'reviews'])
            ->where('status', 'published');

        if ($request->has('limit')) {
            $result = $query->paginate($request->limit);
        } else {
            $result = $query->get();
        }

        return response()->json($result);
    }

    public function getLastSaleProducts(Request $request)
    {
        $products = Product::with('Images')->where('status', '=', 'published')->where('discount', '>', '0')->latest()->take(10)->get();
        return $products;
    }


    public function getLatest(Request $request)
    {
        $products = Product::with('Images')->where('status', '=', 'published')->latest()->take(4)->get();
        return $products;
    }

    public function getTopRated(Request $request)
    {
        $products = Product::with('Images')
            ->where('status', 'published')
            // هنا بنقول له هات من 4 لحد 5
            ->whereBetween('rating', [4, 5])
            // أو ممكن تكتبها كدة: ->where('rating', '>=', 4)

            ->orderBy('rating', 'desc') // عشان يجيب الـ 5 الأول وبعدين الـ 4.9 وهكذا
            ->latest()
            ->take(10)
            ->get();

        return response()->json($products);
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
        $product = new Product();
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'price' => 'required | numeric',
            'discount' => 'required | numeric',
            'About' => 'required',
            'stock' => 'required | numeric'
        ]);
        $productCreated = $product->create([
            'category' => $request->category,
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'About' => $request->About,
            'discount' => $request->discount,
            'stock' => $request->stock

        ]);
        return $productCreated;
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return Product::where('id', $id)->with('Images')->where('status', '=', 'published')->get();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $request->validate([
            'category' => 'required',
            'title' => 'required',
            'description' => 'required',
            'price' => 'required | numeric',
            'discount' => 'required | numeric',
            'stock' => 'required | numeric',
            'About' => 'required'
        ]);
        $product->update([
            'category' => $request->category,
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'About' => $request->About,
            'discount' => $request->discount,
            'stock' => $request->stock

        ]);
        $product->status = 'published';
        $product->save();
        $productId = $product->id;
        if ($request->hasFile('images')) {
            $files = $request->file("images");
            $i = 0;

            foreach ($files as $file) {
                $i++;

                $image = new ProductImage();
                $image->product_id = $productId;

                // Store the file in the 'public/images' directory
                $path = $file->store('images', 'public');

                // Generate the public URL for the stored file
                $image->image = Storage::url($path);

                $image->save();
            }
        }
    }

    // Search On Users
    public function search(Request $request)
    {
        $title = trim($request->input('title'));
        $date = trim($request->input('date'));

        // لو مفيش بحث، رجع مصفوفة فاضية فوراً
        if (empty($title) && empty($date)) {
            return response()->json([]);
        }

        $results = \App\Models\Product::query()
            ->with('images')
            // 🎯 التعديل هنا: استخدمنا use ($title) لضمان دخول المتغير جوه الـ Query
            ->when(!empty($title), function ($query) use ($title) {
                return $query->where('title', 'LIKE', '%' . $title . '%');
            })
            // 🎯 والتعديل هنا أيضاً للـ date
            ->when(!empty($date), function ($query) use ($date) {
                return $query->whereDate('created_at', $date);
            })
            ->latest()
            ->get();

        // تجهيز البيانات للصور
        $results->transform(function ($product) {
            $product->all_images = $product->images;
            $product->image = $product->images->first()?->image;
            return $product;
        });

        return response()->json($results, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $productImages = ProductImage::where('product_id', '=', $id)->get();
        foreach ($productImages as $productImage) {
            if ($productImage->image) {
                // Extract the relative storage path from the image URL
                $path = str_replace(url('/storage'), 'public', $productImage->image);

                // Check if the file exists and delete it
                if (Storage::exists($path)) {
                    Storage::delete($path);
                }
            }
        }
        DB::table('products')->where('id', '=', $id)->delete();
    }
}
