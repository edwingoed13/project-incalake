<?php

namespace Tests\Unit;

use App\Models\Tour;
use App\Services\TourService;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessTourImages;
use Tests\TestCase;

class ServiceLayerTest extends TestCase
{
    public function test_tour_service_can_generate_code(): void
    {
        $service = app(TourService::class);
        $languageId = 1;

        $code1 = $service->generateTourCode($languageId);
        $code2 = $service->generateTourCode($languageId);

        $this->assertNotEquals($code1, $code2);
        $this->assertGreaterThan($code1, $code2);
    }

    public function test_tour_service_can_generate_slug(): void
    {
        $service = app(TourService::class);

        $slug1 = $service->generateSlug('Test Tour 1');
        $slug2 = $service->generateSlug('Test Tour 2');
        $slug3 = $service->generateSlug('Test Tour 1');

        $this->assertEquals('test-tour-1', $slug1);
        $this->assertEquals('test-tour-2', $slug2);
        $this->assertEquals('test-tour-1', $slug3);
    }

    public function test_tour_creation_dispatches_job_for_images(): void
    {
        Queue::fake();

        $tempImages = [
            [
                'filename' => 'test.jpg',
                'path' => 'tours/temp/test.jpg',
            ],
        ];

        Queue::assertNotPushed(ProcessTourImages::class);
    }

    public function test_tour_factory_creates_valid_tour(): void
    {
        $tour = Tour::factory()->create();

        $this->assertDatabaseHas('tours', ['id' => $tour->id]);
        $this->assertNotNull($tour->code);
        $this->assertNotNull($tour->city_name);
    }

    public function test_tour_with_prices_factory_creates_valid_relationships(): void
    {
        $tour = Tour::factory()->hasPrices(3)->create();

        $this->assertCount(3, $tour->prices);
        $this->assertDatabaseHas('tour_prices', ['tour_id' => $tour->id]);
    }

    public function test_tour_with_translations_factory_creates_valid_relationships(): void
    {
        $tour = Tour::factory()->hasTranslations(2)->create();

        $this->assertCount(2, $tour->translations);
        $this->assertDatabaseHas('tour_translations', ['tour_id' => $tour->id]);
    }

    public function test_tour_with_map_points_factory_creates_valid_relationships(): void
    {
        $tour = Tour::factory()->hasMapPoints(5)->create();

        $this->assertCount(5, $tour->mapPoints);
        $this->assertDatabaseHas('tour_map_points', ['tour_id' => $tour->id]);
    }
}