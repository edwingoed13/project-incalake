<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Language;
use App\Models\Tour;
use App\Models\TourTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tour>
 *
 * The test suite has called Tour::factory() since the initial commit but this
 * class never existed, so every tour-related test died on a
 * BadMethodCallException before reaching the code it meant to exercise.
 *
 * Note the default status: the table defaults to 'draft', but a factory-made
 * tour defaults to PUBLISHED here. Tests that say `Tour::factory()->create()`
 * mean "an ordinary, visible tour" — and since unpublished tours are now
 * hidden from the public API, a draft default would make almost every listing
 * assertion fail for reasons the test isn't about. Use ->draft() when the
 * unpublished case IS the subject.
 */
class TourFactory extends Factory
{
    protected $model = Tour::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('TF####')),
            'primary_language_id' => fn () => $this->language()->id,
            'city_id' => fn () => $this->city()->id,
            'city_name' => 'Puno',
            'service_type' => 'tour',
            'status' => 'published',
            'difficulty' => 'easy',
            'target_audience' => 'all',
            'capacity' => 99,
            'cupos' => 20,
            'duration_days' => 1,
            'duration_hours' => 8,
            'active' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft', 'active' => false]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => 'archived', 'active' => false]);
    }

    /**
     * Attach a translation so the tour is reachable by slug and renders a
     * public page. All four columns are NOT NULL in tour_translations.
     */
    public function withTranslation(string $langCode = 'ES', ?string $slug = null): static
    {
        return $this->afterCreating(function (Tour $tour) use ($langCode, $slug) {
            $language = $this->language($langCode);
            $slug = $slug ?: 'tour-' . strtolower($tour->code);

            TourTranslation::create([
                'tour_id' => $tour->id,
                'language_id' => $language->id,
                'slug' => $slug,
                'h1_title' => 'Tour ' . $tour->code,
                'meta_title' => 'Tour ' . $tour->code,
                'meta_description' => 'Descripción de prueba para ' . $tour->code,
            ]);
        });
    }

    /** Reused rather than re-created: languages are a small fixed catalogue. */
    private function language(string $code = 'ES'): Language
    {
        return Language::firstOrCreate(
            ['code' => $code],
            ['country' => $code === 'ES' ? 'Español' : $code]
        );
    }

    private function city(string $name = 'Puno'): City
    {
        return City::firstOrCreate(
            ['name' => $name],
            ['slug' => strtolower($name)]
        );
    }
}
