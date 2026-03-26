<?php

namespace Database\Seeders;

use App\Models\CategoryNew;
use App\Models\Language;
use Illuminate\Database\Seeder;

class CategoriesNewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear categoría base
        $category = CategoryNew::create([
            'code' => 'turismo-aventura',
            'active' => true,
        ]);

        // Obtener todos los idiomas
        $languages = Language::whereIn('code', ['ES', 'EN', 'FR', 'DE', 'PT', 'IT'])->get();

        // Traducciones para cada idioma
        $translations = [
            'ES' => [
                'name' => 'Turismo de Aventura',
                'description' => 'Actividades emocionantes y experiencias únicas para los amantes de la aventura',
            ],
            'EN' => [
                'name' => 'Adventure Tourism',
                'description' => 'Exciting activities and unique experiences for adventure lovers',
            ],
            'FR' => [
                'name' => 'Tourisme d\'Aventure',
                'description' => 'Activités passionnantes et expériences uniques pour les amoureux de l\'aventure',
            ],
            'DE' => [
                'name' => 'Abenteuertourismus',
                'description' => 'Spannende Aktivitäten und einzigartige Erlebnisse für Abenteuerliebhaber',
            ],
            'PT' => [
                'name' => 'Turismo de Aventura',
                'description' => 'Atividades emocionantes e experiências únicas para amantes da aventura',
            ],
            'IT' => [
                'name' => 'Turismo d\'Avventura',
                'description' => 'Attività emozionanti ed esperienze uniche per gli amanti dell\'avventura',
            ],
        ];

        foreach ($languages as $language) {
            $translationData = $translations[$language->code] ?? [
                'name' => 'Adventure Tourism',
                'description' => 'Adventure tourism category',
            ];

            $category->translations()->create([
                'language_id' => $language->id,
                'name' => $translationData['name'],
                'description' => $translationData['description'],
                'slug' => \Str::slug($translationData['name']),
            ]);
        }

        $this->command->info('✓ Categoría "turismo-aventura" creada con 6 traducciones');
    }
}
