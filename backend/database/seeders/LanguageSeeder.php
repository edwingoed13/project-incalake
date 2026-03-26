<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            ['country' => 'ESPAÑOL', 'code' => 'ES', 'user_id' => null],
            ['country' => 'INGLES', 'code' => 'EN', 'user_id' => null],
            ['country' => 'FRANCES', 'code' => 'FR', 'user_id' => null],
            ['country' => 'ALEMAN', 'code' => 'DE', 'user_id' => null],
            ['country' => 'PORTUGUES', 'code' => 'BR', 'user_id' => null],
        ];

        foreach ($languages as $language) {
            Language::create($language);
        }
    }
}
