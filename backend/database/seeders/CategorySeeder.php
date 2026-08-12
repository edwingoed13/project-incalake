<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds one demo category.
 *
 * Rewritten for the current schema. It used to insert name / description /
 * language_id / category_code_id straight into the categories table, which
 * described the pre-split model: one row per language, grouped by a
 * category_code. Today `categories_new` holds only code + active and the text
 * lives in `category_translations`, so the old insert died with "Unknown
 * column 'name'" and took `php artisan db:seed` down with it — a fresh install
 * could not be seeded at all.
 *
 * (Seeding runs with mass-assignment protection off, which is why the stale
 * columns reached the query instead of being quietly dropped by $fillable.)
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['code' => 'turismo-astronomico'],
            ['active' => true]
        );

        $translations = [
            'ES' => ['Turismo Astronómico', 'Observación del cielo nocturno del altiplano.'],
            'EN' => ['Astronomical Tourism', 'Night sky watching over the Andean highlands.'],
            'PT' => ['Turismo Astronômico', 'Observação do céu noturno do altiplano.'],
            'FR' => ['Tourisme Astronomique', 'Observation du ciel nocturne des hauts plateaux.'],
            'DE' => ['Astronomischer Tourismus', 'Nachthimmelbeobachtung im Hochland.'],
            'IT' => ['Turismo Astronomico', 'Osservazione del cielo notturno dell altipiano.'],
        ];

        foreach (Language::all() as $language) {
            $code = strtoupper((string) $language->code);
            [$name, $description] = $translations[$code] ?? $translations['ES'];

            CategoryTranslation::updateOrCreate(
                ['category_id' => $category->id, 'language_id' => $language->id],
                [
                    'name' => $name,
                    'description' => $description,
                    // Slug is per language, so it carries the code to stay unique.
                    'slug' => Str::slug($name . ' ' . $code),
                ]
            );
        }
    }
}
