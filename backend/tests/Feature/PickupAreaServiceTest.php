<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Services\PickupAreaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A wrong answer here either charges a customer for a pickup that was always
 * inside the zone, or refuses one that was. Both are visible to the customer
 * and neither is obvious from reading the maths, so the cases are pinned.
 *
 * Coordinates are around Puno (-15.84, -70.02).
 */
class PickupAreaServiceTest extends TestCase
{
    use RefreshDatabase;

    private PickupAreaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PickupAreaService::class);
    }

    /** A ~2.2 km square centred near Puno. */
    private function squareTour(): Tour
    {
        return Tour::factory()->create([
            'pickup_area_type' => 'polygon',
            'pickup_area' => [
                ['lat' => -15.83, 'lng' => -70.03],
                ['lat' => -15.83, 'lng' => -70.01],
                ['lat' => -15.85, 'lng' => -70.01],
                ['lat' => -15.85, 'lng' => -70.03],
            ],
        ]);
    }

    public function test_a_point_inside_the_drawn_area_is_inside(): void
    {
        $r = $this->service->evaluate($this->squareTour(), -15.84, -70.02);

        $this->assertTrue($r['inside']);
        $this->assertSame(0.0, $r['distance_outside_km']);
    }

    public function test_a_point_outside_the_drawn_area_is_outside(): void
    {
        // Well east of the square.
        $r = $this->service->evaluate($this->squareTour(), -15.84, -69.99);

        $this->assertFalse($r['inside']);
        $this->assertGreaterThan(0, $r['distance_outside_km']);
    }

    public function test_distance_is_measured_to_the_nearest_edge_not_the_centre(): void
    {
        // ~0.01 degrees of longitude past the eastern edge: roughly 1 km at
        // this latitude. Measured from the centre it would read about 2 km, so
        // this is the assertion that catches a centre-based shortcut.
        $r = $this->service->evaluate($this->squareTour(), -15.84, -70.00);

        $this->assertFalse($r['inside']);
        $this->assertEqualsWithDelta(1.07, $r['distance_outside_km'], 0.25);
    }

    public function test_two_separate_zones_are_both_valid(): void
    {
        $tour = Tour::factory()->create([
            'pickup_area_type' => 'polygon',
            'pickup_area' => [
                [
                    ['lat' => -15.83, 'lng' => -70.03],
                    ['lat' => -15.83, 'lng' => -70.02],
                    ['lat' => -15.84, 'lng' => -70.02],
                    ['lat' => -15.84, 'lng' => -70.03],
                ],
                [
                    ['lat' => -15.86, 'lng' => -70.01],
                    ['lat' => -15.86, 'lng' => -70.00],
                    ['lat' => -15.87, 'lng' => -70.00],
                    ['lat' => -15.87, 'lng' => -70.01],
                ],
            ],
        ]);

        $this->assertTrue($this->service->evaluate($tour, -15.835, -70.025)['inside']);
        $this->assertTrue($this->service->evaluate($tour, -15.865, -70.005)['inside']);
        // Between the two zones is still outside.
        $this->assertFalse($this->service->evaluate($tour, -15.85, -70.015)['inside']);
    }

    public function test_an_l_shaped_area_excludes_its_notch(): void
    {
        // The point of drawing instead of using a circle: a concave shape has
        // to reject the bite taken out of it. A circle covering the same span
        // would accept it.
        $tour = Tour::factory()->create([
            'pickup_area_type' => 'polygon',
            'pickup_area' => [
                ['lat' => -15.83, 'lng' => -70.03],
                ['lat' => -15.83, 'lng' => -70.01],
                ['lat' => -15.84, 'lng' => -70.01],
                ['lat' => -15.84, 'lng' => -70.02],
                ['lat' => -15.85, 'lng' => -70.02],
                ['lat' => -15.85, 'lng' => -70.03],
            ],
        ]);

        $this->assertTrue($this->service->evaluate($tour, -15.835, -70.015)['inside']);
        // Inside the bounding box, outside the L.
        $this->assertFalse($this->service->evaluate($tour, -15.845, -70.015)['inside']);
    }

    public function test_a_polygon_tour_with_no_shape_never_grants_free_pickup(): void
    {
        // Misconfiguration must not read as "everywhere is covered".
        foreach ([null, [], [['lat' => -15.83, 'lng' => -70.03]]] as $area) {
            $tour = Tour::factory()->create([
                'pickup_area_type' => 'polygon',
                'pickup_area' => $area,
            ]);

            $this->assertFalse(
                $this->service->evaluate($tour, -15.84, -70.02)['inside'],
                'An unusable area was treated as covering the point'
            );
        }
    }

    public function test_radius_tours_are_untouched(): void
    {
        $tour = Tour::factory()->create([
            'pickup_area_type' => 'radius',
            'pickup_center_lat' => -15.84,
            'pickup_center_lng' => -70.02,
            'pickup_radius_km' => 3,
        ]);

        $inside = $this->service->evaluate($tour, -15.845, -70.025);
        $this->assertTrue($inside['inside']);

        // ~5.5 km north of the centre.
        $outside = $this->service->evaluate($tour, -15.79, -70.02);
        $this->assertFalse($outside['inside']);
        $this->assertEqualsWithDelta(
            $outside['distance_km'] - 3,
            $outside['distance_outside_km'],
            0.01
        );
    }

    public function test_a_tour_defaults_to_radius(): void
    {
        $tour = Tour::factory()->create([
            'pickup_center_lat' => -15.84,
            'pickup_center_lng' => -70.02,
            'pickup_radius_km' => 3,
        ]);

        $this->assertSame('radius', $tour->fresh()->pickup_area_type);
    }
}
