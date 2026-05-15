<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. إنشاء المستخدم (لو مش موجود)
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'admin',
                'password' => bcrypt('123456'),
                'role' => 1995
            ]
        );

        // 2. إنشاء 10 أقسام أولاً
        $categories = Category::factory(10)->create();

        // 3. إنشاء منتجات مربوطة بالأقسام اللي لسه معمولة
        Product::factory(50)->make()->each(function ($product) use ($categories) {
            $product->category_id = $categories->random()->id;
            $product->save();

            // 4. إنشاء صور لكل منتج بالمرة
            ProductImage::factory(3)->create([
                'product_id' => $product->id
            ]);
        });
    }
}
