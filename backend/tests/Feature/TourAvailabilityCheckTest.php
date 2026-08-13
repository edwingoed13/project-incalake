<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Services\TourAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * These rules must match TourCalendar.vue exactly, and the cost of drifting
 * runs both ways: stricter than the calendar and real customers are refused
 * dates they were shown as free; looser and unbookable dates get sold.
 */
class TourAvailabilityCheckTest extends TestCase
{
    use RefreshDatabase;

    private TourAvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TourAvailabilityService::class);
    }

    private function tour(array $availability): Tour
    {
        return Tour::factory()->create(['availability_data' => $availability]);
    }

    /** Every weekday open, so the tests below isolate one rule at a time. */
    private const ALL_DAYS = [0, 1, 2, 3, 4, 5, 6];

    public function test_a_date_inside_the_window_is_available(): void
    {
        $tour = $this->tour([
            'start' => now()->subYear()->toDateString(),
            'end' => now()->addYear()->toDateString(),
            'activeDays' => self::ALL_DAYS,
        ]);

        $this->assertTrue($this->service->check($tour, now()->addDays(10)->toDateString())['available']);
    }

    public function test_a_date_after_the_end_is_refused(): void
    {
        // The 133-tour case: still published, still says bookable, calendar
        // closed. Before this the API would have taken the booking.
        $tour = $this->tour([
            'start' => '2017-01-01',
            'end' => '2019-12-31',
            'activeDays' => self::ALL_DAYS,
        ]);

        $result = $this->service->check($tour, now()->addDays(5)->toDateString());

        $this->assertFalse($result['available']);
        $this->assertStringContainsString('ya no está disponible', $result['reason']);
    }

    public function test_a_date_before_the_start_is_refused(): void
    {
        $tour = $this->tour([
            'start' => now()->addMonths(6)->toDateString(),
            'end' => now()->addYear()->toDateString(),
            'activeDays' => self::ALL_DAYS,
        ]);

        $this->assertFalse($this->service->check($tour, now()->addDays(5)->toDateString())['available']);
    }

    public function test_a_tour_that_never_expires_has_no_upper_bound(): void
    {
        $tour = $this->tour([
            'start' => '2020-01-01',
            'end' => '',
            'neverExpires' => true,
            'activeDays' => self::ALL_DAYS,
        ]);

        $this->assertTrue($this->service->check($tour, now()->addYears(5)->toDateString())['available']);
    }

    public function test_a_weekday_the_tour_does_not_operate_is_refused(): void
    {
        // Sundays only (0), so ask for the next Monday.
        $tour = $this->tour([
            'start' => now()->subYear()->toDateString(),
            'end' => now()->addYear()->toDateString(),
            'activeDays' => [0],
        ]);

        $monday = now()->next(\Carbon\Carbon::MONDAY)->toDateString();
        $result = $this->service->check($tour, $monday);

        $this->assertFalse($result['available']);
        $this->assertStringContainsString('día de la semana', $result['reason']);
    }

    public function test_a_blocked_range_is_refused(): void
    {
        $from = now()->addDays(5);
        $tour = $this->tour([
            'start' => now()->subYear()->toDateString(),
            'end' => now()->addYear()->toDateString(),
            'activeDays' => self::ALL_DAYS,
            'blocks' => [[
                'startDate' => $from->toDateString(),
                'endDate' => $from->copy()->addDays(3)->toDateString(),
            ]],
        ]);

        $this->assertFalse($this->service->check($tour, $from->copy()->addDay()->toDateString())['available']);
        // Just outside the block is still fine.
        $this->assertTrue($this->service->check($tour, $from->copy()->addDays(5)->toDateString())['available']);
    }

    public function test_a_recurring_holiday_is_refused(): void
    {
        // specialDays are DD-MM so they repeat every year.
        $target = now()->addDays(20);
        $tour = $this->tour([
            'start' => now()->subYear()->toDateString(),
            'end' => now()->addYear()->toDateString(),
            'activeDays' => self::ALL_DAYS,
            'specialDays' => [$target->format('d-m')],
        ]);

        $this->assertFalse($this->service->check($tour, $target->toDateString())['available']);
    }

    public function test_a_past_date_is_refused(): void
    {
        $tour = $this->tour([
            'start' => '2020-01-01',
            'end' => now()->addYear()->toDateString(),
            'activeDays' => self::ALL_DAYS,
        ]);

        $this->assertFalse($this->service->check($tour, now()->subDay()->toDateString())['available']);
    }

    public function test_a_tour_without_availability_data_stays_bookable(): void
    {
        // Absent configuration must not become an accidental blanket refusal:
        // that would take working tours offline the moment this shipped.
        $tour = Tour::factory()->create(['availability_data' => null]);

        $this->assertTrue($this->service->check($tour, now()->addDays(3)->toDateString())['available']);
    }

    public function test_the_booking_endpoint_refuses_an_expired_tour(): void
    {
        $tour = $this->tour([
            'start' => '2017-01-01',
            'end' => '2019-12-31',
            'activeDays' => self::ALL_DAYS,
        ]);

        $this->postJson('/api/bookings', [
            'tour_id' => $tour->id,
            'tour_date' => now()->addDays(5)->toDateString(),
            'adults' => 2,
            'customer_name' => 'Prueba',
            'customer_email' => 'prueba@example.com',
        ])->assertStatus(422);

        $this->assertDatabaseCount('bookings', 0);
    }
}
