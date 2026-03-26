<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            ['code' => 'ES', 'country' => 'ESPAÑOL'],
            ['code' => 'EN', 'country' => 'ENGLISH'],
            ['code' => 'FR', 'country' => 'FRANÇAIS'],
            ['code' => 'DE', 'country' => 'DEUTSCH'],
            ['code' => 'PT', 'country' => 'PORTUGUÊS'],
            ['code' => 'IT', 'country' => 'ITALIANO'],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(
                ['code' => $language['code']],
                ['country' => $language['country']]
            );
        }

        $this->command->info('✓ 6 idiomas creados/actualizados correctamente');
    }
}
