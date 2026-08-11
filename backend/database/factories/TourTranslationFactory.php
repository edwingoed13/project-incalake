<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\Tour;
use App\Models\TourTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TourTranslation>
 *
 * slug, h1_title, meta_title and meta_description are all NOT NULL, so every
 * one of them needs a value here or ->hasTranslations() blows up.
 */
class TourTranslationFactory extends Factory
{
    protected $model = TourTranslation::class;

    public function definition(): array
    {
        $slug = $this->faker->unique()->slug(3);

        return [
            'tour_id' => Tour::factory(),
            // Cycles through the real locale set so hasTranslations(2) doesn't
            // collide on (tour_id, language_id).
            'language_id' => fn () => $this->nextLanguage()->id,
            'slug' => $slug,
            'h1_title' => $this->faker->sentence(4),
            'meta_title' => $this->faker->sentence(3),
            'meta_description' => $this->faker->sentence(8),
        ];
    }

    private static int $languageCursor = 0;

    private function nextLanguage(): Language
    {
        $codes = ['ES', 'EN', 'PT', 'FR', 'DE', 'IT'];
        $code = $codes[self::$languageCursor++ % count($codes)];

        return Language::firstOrCreate(['code' => $code], ['country' => $code]);
    }
}
