<?php

namespace Tests\Feature;

use App\Mail\BookingTravelersCompletedMail;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * A deposit is not a settled booking, and the operator has to know which.
 *
 * `payment_status` reads "paid" the moment a charge goes through — every
 * booking in the live database carries it, deposits included. The operator's
 * notification took that at face value and printed "✓ Pagado · USD 781.10" on
 * a booking where PayPal had taken 234.33 and the desk still had to collect
 * 546.77 in cash. The amount actually charged is the only thing that separates
 * the two, and it sits in payment_data under four keys depending on the
 * gateway; that derivation now lives on the model, so the email and the admin
 * listing cannot drift apart.
 */
class BookingPaymentStateTest extends TestCase
{
    use RefreshDatabase;

    private function booking(array $extra = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'total' => 781.10,
            'currency' => 'USD',
            'payment_status' => 'paid',
            'payment_method' => 'paypal',
        ], $extra));
    }

    public function test_it_reads_the_amount_charged_whichever_gateway_recorded_it(): void
    {
        $formas = [
            ['group_total_charged' => 23433],            // Culqi, cents
            ['group_total_captured' => 234.33],          // PayPal, units
            ['charge_data' => ['amount' => 23433]],
            ['amount_cents' => 23433],
        ];

        foreach ($formas as $pd) {
            $b = $this->booking(['payment_data' => $pd]);
            $this->assertSame(234.33, $b->chargedAmount(), json_encode($pd));
        }
    }

    public function test_a_deposit_leaves_a_balance_to_collect(): void
    {
        $b = $this->booking(['payment_data' => ['amount_cents' => 23433]]);

        $this->assertSame(546.77, $b->outstandingAmount());
    }

    public function test_a_full_charge_leaves_nothing(): void
    {
        $b = $this->booking(['payment_data' => ['amount_cents' => 78110]]);

        $this->assertSame(0.0, $b->outstandingAmount());
    }

    public function test_rounding_between_gateway_and_total_is_not_a_balance(): void
    {
        // Half a dollar short is currency rounding, not money owed.
        $b = $this->booking(['payment_data' => ['amount_cents' => 78080]]);

        $this->assertSame(0.0, $b->outstandingAmount());
    }

    public function test_an_unpaid_booking_owes_nothing_yet(): void
    {
        $b = $this->booking(['payment_status' => 'pending', 'payment_data' => null]);

        $this->assertSame(0.0, $b->outstandingAmount());
    }

    public function test_the_operator_email_names_the_gateway(): void
    {
        $paypal = (new BookingTravelersCompletedMail(
            $this->booking(['payment_data' => ['amount_cents' => 78110]])
        ))->render();
        $culqi = (new BookingTravelersCompletedMail(
            $this->booking(['payment_method' => 'culqi', 'payment_data' => ['amount_cents' => 78110]])
        ))->render();

        $this->assertStringContainsString('PAYPAL', $paypal);
        $this->assertStringContainsString('CULQI', $culqi);
    }

    public function test_the_operator_email_does_not_call_a_deposit_paid_in_full(): void
    {
        $html = (new BookingTravelersCompletedMail(
            $this->booking(['payment_data' => ['amount_cents' => 23433]])
        ))->render();

        $this->assertStringContainsString('Adelanto pagado', $html);
        $this->assertStringContainsString('234.33', $html);
        $this->assertStringContainsString('A cobrar el día del tour', $html);
        $this->assertStringContainsString('546.77', $html);
        // The line that used to send the operator away empty-handed.
        $this->assertStringNotContainsString('✓ Pagado', $html);
    }

    public function test_the_operator_email_still_says_paid_when_it_is(): void
    {
        $html = (new BookingTravelersCompletedMail(
            $this->booking(['payment_data' => ['amount_cents' => 78110]])
        ))->render();

        $this->assertStringContainsString('✓ Pagado', $html);
        $this->assertStringNotContainsString('A cobrar el día del tour', $html);
    }

    public function test_the_admin_listing_still_reports_a_partial_payment(): void
    {
        // The listing derives the same numbers through the model now; this is
        // the regression guard for that move.
        $this->booking(['payment_data' => ['amount_cents' => 23433]]);
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $fila = $this->getJson('/api/bookings?per_page=5')->assertOk()->json('data.0');

        $this->assertSame('partial', $fila['payment_state']);
        $this->assertEquals(234.33, $fila['amount_paid']);
        $this->assertEquals(546.77, $fila['amount_remaining']);
    }
}
