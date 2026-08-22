<?php

namespace Tests\Feature;

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BK-26-0822-1234. The running number is gone, so the four random digits are
 * now the only thing keeping two bookings on the same day apart — against a
 * unique index. That makes uniqueness the property worth pinning, not the
 * shape.
 */
class BookingCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_code_reads_as_year_month_day_and_four_digits(): void
    {
        $code = Booking::generateBookingCode();

        $this->assertMatchesRegularExpression('/^BK-\d{2}-\d{4}-\d{4}$/', $code);
        $this->assertSame('BK-' . date('y') . '-' . date('md'), substr($code, 0, 11));
        $this->assertLessThanOrEqual(20, strlen($code), 'the column is string(20)');
    }

    /** The generator must not hand back a code that already exists. */
    public function test_it_skips_a_code_that_is_already_taken(): void
    {
        $taken = [];

        // Occupy a slice of the day's space, then keep generating: every code
        // must be new, not merely well-formed.
        for ($i = 0; $i < 40; $i++) {
            $code = Booking::generateBookingCode();
            $this->assertNotContains($code, $taken, 'generated a code that was already issued today');
            $taken[] = $code;

            Booking::factory()->create(['booking_code' => $code]);
        }
    }
}
