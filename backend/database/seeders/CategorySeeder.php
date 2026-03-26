<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryCode;
use App\Models\Language;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user if doesn't exist
        $user = User::firstOrCreate(
            ['email' => 'admin@incalake.com'],
            [
                'name' => 'Admin Incalake',
                'password' => bcrypt('password'),
            ]
        );

        // Create category code
        $categoryCode = CategoryCode::create([
            'code' => 'turismo-astronomico',
            'image_id' => null,
        ]);

        // Get all languages
        $languages = Language::all();

        // Create category in all languages
        foreach ($languages as $language) {
            Category::create([
                'name' => 'Turismo Astronómico',
                'description' => 'Turismo Astronómico',
                'language_id' => $language->id,
                'category_code_id' => $categoryCode->id,
                'user_id' => $user->id,
            ]);
        }
    }
}
