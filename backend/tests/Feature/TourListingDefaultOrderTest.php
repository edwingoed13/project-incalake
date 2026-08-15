<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Review;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The public listing's default order is "best sellers first" — real paid,
 * non-cancelled bookings, with published reviews as the tie-breaker. A newly
 * created tour must NOT open the listing just for being newest.
 */
class TourListingDefaultOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function listedIds(): array
    {
        return array_column(
            $this->getJson('/api/tours?light=1&per_page=50')->assertOk()->json('data'),
            'id'
        );
    }

    public function test_paid_bookings_rank_a_tour_first_and_cancelled_ones_do_not_count(): void
    {
        $newest = Tour::factory()->create();               // newest, zero sales
        $seller = Tour::factory()->create();
        $cancelled = Tour::factory()->create();

        Booking::factory()->count(2)->create([
            'tour_id' => $seller->id, 'payment_status' => 'paid', 'status' => 'confirmed',
        ]);
        // Paid but cancelled — must not count as a sale.
        Booking::factory()->create([
            'tour_id' => $cancelled->id, 'payment_status' => 'paid', 'status' => 'cancelled',
        ]);

        $ids = $this->listedIds();

        $this->assertSame($seller->id, $ids[0], 'the tour with real sales opens the listing');
        $this->assertContains($newest->id, $ids);
    }

    public function test_reviews_break_ties_between_tours_without_sales(): void
    {
        $plain = Tour::factory()->create();
        $reviewed = Tour::factory()->create();
        Review::create(['tour_id' => $reviewed->id, 'name' => 'Ana', 'rating' => 5, 'comment' => 'Top', 'published' => true]);

        $ids = $this->listedIds();

        $this->assertTrue(
            array_search($reviewed->id, $ids) < array_search($plain->id, $ids),
            'with zero sales each, the reviewed tour ranks above the plain one'
        );
    }

    public function test_an_explicit_sort_by_still_wins(): void
    {
        $a = Tour::factory()->create(['code' => 'AAA1']);
        $b = Tour::factory()->create(['code' => 'ZZZ9']);
        Booking::factory()->create(['tour_id' => $a->id, 'payment_status' => 'paid', 'status' => 'confirmed']);

        $ids = array_column(
            $this->getJson('/api/tours?light=1&per_page=50&sort_by=code&sort_order=desc')->assertOk()->json('data'),
            'id'
        );

        $this->assertTrue(array_search($b->id, $ids) < array_search($a->id, $ids));
    }
}
