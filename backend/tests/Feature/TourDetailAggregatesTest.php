<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\City;
use App\Models\Language;
use App\Models\Review;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The tour page's header numbers and its MÁS VENDIDO badge come from here.
 * The badge used to mean "has ≥20 reviews", which crowned a Uyuni package
 * whose 57 reviews belonged to a 3-hour Uros boat tour. Best seller = real
 * paid sales, and the rating/count aggregate over ALL published reviews.
 */
class TourDetailAggregatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function makeTourWithSlug(string $slug): Tour
    {
        $city = City::firstOrCreate(
            ['slug' => 'puno'],
            ['name' => 'Puno', 'country_code' => 'PE', 'timezone' => 'America/Lima', 'active' => true]
        );

        $tour = Tour::factory()->withTranslation('ES', $slug)->create(['city_id' => $city->id]);

        return $tour;
    }

    public function test_many_reviews_alone_do_not_make_a_best_seller(): void
    {
        $tour = $this->makeTourWithSlug('paquete-uyuni-test');
        for ($i = 0; $i < 25; $i++) {
            Review::create(['tour_id' => $tour->id, 'name' => "R$i", 'rating' => 5, 'comment' => 'x', 'published' => true]);
        }

        $data = $this->getJson('/api/tours/es/puno/paquete-uyuni-test')->assertOk()->json('data');

        $this->assertFalse($data['is_best_seller'], '25 reviews without sales must not earn the badge');
        $this->assertSame(25, $data['reviews_count']);
        $this->assertEquals(5, $data['rating']);
    }

    public function test_real_paid_sales_earn_the_badge(): void
    {
        $tour = $this->makeTourWithSlug('uros-vendedor-test');
        Booking::factory()->count(5)->create([
            'tour_id' => $tour->id, 'payment_status' => 'paid', 'status' => 'confirmed',
        ]);

        $data = $this->getJson('/api/tours/es/puno/uros-vendedor-test')->assertOk()->json('data');

        $this->assertTrue($data['is_best_seller']);
        $this->assertNull($data['rating'], 'no published reviews → null rating, never a fabricated one');
    }
}
