<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The listing cards show "★ 4.5 (20)" as social proof. The one hard rule:
 * stars come only from PUBLISHED reviews, and a tour without reviews reports
 * null — the frontend must never be able to render fabricated stars.
 */
class TourListingRatingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();   // the public listing is cached; don't inherit stale payloads
    }

    private function listedCard(int $tourId): ?array
    {
        $data = $this->getJson('/api/tours?light=1&per_page=50')->assertOk()->json('data');

        return collect($data)->firstWhere('id', $tourId);
    }

    public function test_cards_carry_the_average_and_count_of_published_reviews(): void
    {
        $tour = Tour::factory()->create();
        Review::create(['tour_id' => $tour->id, 'name' => 'Ana', 'rating' => 5, 'comment' => 'Excelente', 'published' => true]);
        Review::create(['tour_id' => $tour->id, 'name' => 'Luis', 'rating' => 4, 'comment' => 'Muy bueno', 'published' => true]);

        $card = $this->listedCard($tour->id);

        $this->assertSame(4.5, $card['rating']);
        $this->assertSame(2, $card['reviews_count']);
    }

    public function test_unpublished_reviews_do_not_count(): void
    {
        $tour = Tour::factory()->create();
        Review::create(['tour_id' => $tour->id, 'name' => 'Ana', 'rating' => 5, 'comment' => 'Publicada', 'published' => true]);
        Review::create(['tour_id' => $tour->id, 'name' => 'Bot', 'rating' => 1, 'comment' => 'Oculta', 'published' => false]);

        $card = $this->listedCard($tour->id);

        // assertEquals, not assertSame: a whole-number average (5.0) loses its
        // decimal in the JSON round-trip and arrives as int 5.
        $this->assertEquals(5, $card['rating']);
        $this->assertSame(1, $card['reviews_count']);
    }

    public function test_a_tour_without_reviews_reports_null_rating(): void
    {
        $tour = Tour::factory()->create();

        $card = $this->listedCard($tour->id);

        $this->assertNull($card['rating']);
        $this->assertSame(0, $card['reviews_count']);
    }
}
