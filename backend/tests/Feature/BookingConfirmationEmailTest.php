<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmationEmail;
use App\Mail\GroupBookingConfirmationEmail;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The office asked for ONE email per booking that shows exactly what the
 * customer received while still arriving as "Nueva Reserva Confirmada". So the
 * admin variant must be a superset of the client one: same body, plus an ops
 * strip. These tests pin that contract — if someone re-forks the template into
 * two renderings, the superset assertions break.
 */
class BookingConfirmationEmailTest extends TestCase
{
    use RefreshDatabase;

    private function booking(array $extra = []): Booking
    {
        return Booking::factory()->paid()->create(array_merge([
            'tour_title'     => 'Tour a Los Uros y Taquile',
            'customer_name'  => 'Ana Quispe',
            'customer_email' => 'ana@example.com',
            'customer_phone' => '+51 999 888 777',
            'tour_time'      => '07:00',
            'subtotal'       => 158.00,
            'total'          => 181.70,
        ], $extra));
    }

    public function test_client_email_has_no_ops_strip(): void
    {
        $html = (new BookingConfirmationEmail($this->booking(), false, 181.70))->render();

        $this->assertStringNotContainsString('NUEVA RESERVA CONFIRMADA', $html);
        $this->assertStringNotContainsString('copia exacta', $html);
        $this->assertStringContainsString('Ana Quispe', $html);
        $this->assertStringContainsString('Ver detalles de mi reserva', $html);
    }

    public function test_admin_email_is_the_client_email_plus_the_ops_strip(): void
    {
        $booking = $this->booking();

        $html = (new BookingConfirmationEmail($booking, true, 181.70))->render();

        // Ops strip: what the office needs at a glance.
        $this->assertStringContainsString('NUEVA RESERVA CONFIRMADA', $html);
        $this->assertStringContainsString('wa.me/51999888777', $html);
        $this->assertStringContainsString('ana@example.com', $html);
        // And the FULL client body below it — not a summary of it.
        $this->assertStringContainsString('Hola', $html);
        $this->assertStringContainsString('Ver detalles de mi reserva', $html);
        $this->assertStringContainsString($booking->booking_code, $html);
        $this->assertStringContainsString('Resumen de pago', $html);
    }

    public function test_admin_subject_says_new_booking_and_client_subject_says_confirmation(): void
    {
        $booking = $this->booking();

        $admin = (new BookingConfirmationEmail($booking, true))->envelope()->subject;
        $client = (new BookingConfirmationEmail($booking, false))->envelope()->subject;

        $this->assertStringContainsString('Nueva Reserva Confirmada', $admin);
        $this->assertStringContainsString('Confirmacion de Reserva', $client);
    }

    public function test_partial_payment_says_what_is_still_owed_and_to_whom(): void
    {
        $html = (new BookingConfirmationEmail($this->booking(), false, 90.85))->render();

        $this->assertStringContainsString('Pagado ahora', $html);
        $this->assertStringContainsString('90.85', $html);
        // "Saldo" is the word a bank uses for money you HAVE, so the deposit
        // email read like credit waiting at the desk rather than a debt.
        $this->assertStringContainsString('A pagar el dia del tour', $html);
        $this->assertStringNotContainsString('Saldo pendiente', $html);
        $this->assertStringContainsString('Se paga en efectivo al operador', $html);
    }

    public function test_group_email_renders_both_variants_with_every_tour(): void
    {
        $bookings = collect([
            $this->booking(['booking_code' => 'BK-G1-AAAA']),
            $this->booking(['tour_title' => 'Tour a Sillustani', 'booking_code' => 'BK-G2-BBBB']),
        ]);

        $client = (new GroupBookingConfirmationEmail($bookings, false, 300.00))->render();
        $admin  = (new GroupBookingConfirmationEmail($bookings, true, 300.00))->render();

        foreach (['Tour a Los Uros y Taquile', 'Tour a Sillustani', 'BK-G1-AAAA', 'BK-G2-BBBB'] as $needle) {
            $this->assertStringContainsString($needle, $client);
            $this->assertStringContainsString($needle, $admin);
        }
        $this->assertStringNotContainsString('NUEVA RESERVA CONFIRMADA', $client);
        $this->assertStringContainsString('NUEVA RESERVA CONFIRMADA', $admin);
    }
}
