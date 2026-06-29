<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. إنشاء المستخدم (لو مش موجود)
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'first_name' => 'magdy',
                'last_name' => 'elbaz',
                'password' => bcrypt('12345678'),
                'role' => 1995
            ]
        );
    }
}
