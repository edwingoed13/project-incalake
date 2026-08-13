<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The home page's destination grid takes its photo from here. Before this, the
 * frontend held a hardcoded Unsplash map in which Puno and Copacabana shared a
 * photo and Cusco and Juliaca both showed Machu Picchu.
 */
class CityImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();   // the city list is cached; don't inherit another test's copy
    }

    public function test_a_city_exposes_the_photo_of_its_newest_published_tour(): void
    {
        $city = City::create([
            'name' => 'Puno', 'slug' => 'puno', 'country_code' => 'PE',
            'timezone' => 'America/Lima', 'active' => true,
        ]);

        Tour::factory()->create(['city_id' => $city->id, 'featured_image_path' => 'tours/vieja.jpg']);
        Tour::factory()->create(['city_id' => $city->id, 'featured_image_path' => 'tours/nueva.jpg']);

        $row = collect($this->getJson('/api/cities')->assertOk()->json('data'))
            ->firstWhere('slug', 'puno');

        $this->assertStringContainsString('tours/nueva.jpg', $row['image']);
    }

    public function test_it_ignores_unpublished_tours_and_reports_null_when_there_is_no_photo(): void
    {
        $city = City::create([
            'name' => 'Juliaca', 'slug' => 'juliaca', 'country_code' => 'PE',
            'timezone' => 'America/Lima', 'active' => true,
        ]);

        Tour::factory()->draft()->create(['city_id' => $city->id, 'featured_image_path' => 'tours/borrador.jpg']);
        Tour::factory()->create(['city_id' => $city->id, 'featured_image_path' => null]);

        $row = collect($this->getJson('/api/cities')->assertOk()->json('data'))
            ->firstWhere('slug', 'juliaca');

        $this->assertNull($row['image']);
    }
}
