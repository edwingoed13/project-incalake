<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\Language;
use App\Models\CategoryNew;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TourManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_list_tours_via_api(): void
    {
        Tour::factory()->count(3)->create();

        $response = $this->getJson('/api/tours');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'current_page',
                    'total',
                    'per_page',
                    'last_page'
                ]
            ]);
    }

    public function test_can_show_single_tour_via_api(): void
    {
        $tour = Tour::factory()->create();

        $response = $this->getJson("/api/tours/{$tour->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $tour->id,
                    'code' => $tour->code,
                ]
            ]);
    }

    public function test_tour_can_be_bookable(): void
    {
        $tour = Tour::factory()->create([
            'status' => 'published',
            'active' => true,
            'cupos' => 10,
        ]);

        $this->assertTrue($tour->isBookable());
    }

    public function test_tour_draft_cannot_be_bookable(): void
    {
        $tour = Tour::factory()->create([
            'status' => 'draft',
            'active' => true,
        ]);

        $this->assertFalse($tour->isBookable());
    }

    public function test_tour_with_no_cupos_cannot_be_bookable(): void
    {
        $tour = Tour::factory()->create([
            'status' => 'published',
            'active' => true,
            'cupos' => 0,
        ]);

        $this->assertFalse($tour->isBookable());
    }

    public function test_tour_has_min_price(): void
    {
        $tour = Tour::factory()->hasPrices(3)->create();

        $minPrice = $tour->min_price;
        $this->assertNotNull($minPrice);
        $this->assertGreaterThan(0, $minPrice);
    }

    public function test_tour_belongs_to_categories(): void
    {
        $tour = Tour::factory()->create();
        $category = CategoryNew::factory()->create();

        $tour->categories()->attach($category->id);

        $this->assertTrue($tour->categories->contains($category));
    }

    public function test_tour_has_translations(): void
    {
        $language = Language::factory()->create(['code' => 'ES']);
        $tour = Tour::factory()
            ->hasTranslations(1, ['language_id' => $language->id])
            ->create(['primary_language_id' => $language->id]);

        $this->assertCount(1, $tour->translations);
        $this->assertEquals($language->id, $tour->translations->first()->language_id);
    }

    public function test_tour_has_map_points(): void
    {
        $tour = Tour::factory()->hasMapPoints(3)->create();

        $this->assertCount(3, $tour->mapPoints);
    }

    public function test_active_scope_returns_only_active_tours(): void
    {
        Tour::factory()->create(['active' => false]);
        Tour::factory()->create(['active' => true]);
        Tour::factory()->create(['active' => true]);

        $activeTours = Tour::active()->get();

        $this->assertCount(2, $activeTours);
    }

    public function test_published_scope_returns_only_published_tours(): void
    {
        Tour::factory()->create(['status' => 'draft']);
        Tour::factory()->create(['status' => 'published']);
        Tour::factory()->create(['status' => 'published']);

        $publishedTours = Tour::published()->get();

        $this->assertCount(2, $publishedTours);
    }

    public function test_bookable_scope_returns_only_bookable_tours(): void
    {
        Tour::factory()->create(['status' => 'draft', 'active' => true]);
        Tour::factory()->create(['status' => 'published', 'active' => false]);
        Tour::factory()->create(['status' => 'published', 'active' => true, 'cupos' => 0]);
        Tour::factory()->create(['status' => 'published', 'active' => true, 'cupos' => 10]);

        $bookableTours = Tour::bookable()->get();

        $this->assertCount(1, $bookableTours);
    }

    public function test_can_query_tours_by_city(): void
    {
        $cityId = 123;
        Tour::factory()->create(['city_id' => $cityId]);
        Tour::factory()->create(['city_id' => 456]);
        Tour::factory()->create(['city_id' => $cityId]);

        $toursInCity = Tour::byCity($cityId)->get();

        $this->assertCount(2, $toursInCity);
    }

    public function test_can_query_tours_by_service_type(): void
    {
        Tour::factory()->create(['service_type' => 'tour']);
        Tour::factory()->create(['service_type' => 'package']);
        Tour::factory()->create(['service_type' => 'tour']);

        $tourTours = Tour::byServiceType('tour')->get();

        $this->assertCount(2, $tourTours);
    }

    public function test_tour_has_full_duration_attribute(): void
    {
        $tour = Tour::factory()->create([
            'duration_days' => 2,
            'duration_hours' => 3,
        ]);

        $fullDuration = $tour->full_duration;

        $this->assertStringContainsString('2 days', $fullDuration);
        $this->assertStringContainsString('3 hours', $fullDuration);
    }
}