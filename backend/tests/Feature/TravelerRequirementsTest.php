<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Datos requeridos del cliente" is a promise, not a display setting.
 *
 * The wizard let an operator tick the fields a tour needs and choose between
 * "solo lider" and "todos los pasajeros". All it ever did was decide which
 * inputs the traveler form DREW: the browser nagged the lead traveler only, a
 * passenger left blank was filtered out of the payload rather than blocking it,
 * and this endpoint stored whatever it was handed. On one tour set to "todos
 * los pasajeros", 18 of 20 real bookings came back with fewer travelers than
 * seats or a leader missing the very fields that were ticked.
 *
 * The autosave still has to accept half-finished work — that is what lets a
 * customer close the tab and come back — so the check hangs off `finalize`,
 * which only the actual submit sends.
 */
class TravelerRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private function tour(int $requirement, array $personal = [], array $operational = []): Tour
    {
        return Tour::factory()->create([
            'data_requirement' => $requirement,
            'personal_info_required' => $personal,
            'operational_info_required' => $operational,
        ]);
    }

    private function booking(Tour $tour, int $adults = 2): Booking
    {
        return Booking::factory()->create([
            'tour_id' => $tour->id,
            'adults' => $adults,
            'children' => 0,
            'total_participants' => $adults,
            'customer_email' => 'viajero@example.com',
        ]);
    }

    private function save(Booking $booking, array $travelers, bool $finalize)
    {
        return $this->postJson("/api/bookings/{$booking->id}/travelers?email=viajero@example.com", [
            'travelers' => $travelers,
            'finalize' => $finalize,
        ]);
    }

    public function test_the_autosave_still_takes_half_finished_work(): void
    {
        $tour = $this->tour(2, ['nationality', 'birthdate'], ['hotel_name']);
        $booking = $this->booking($tour);

        // Exactly what a customer who typed their name and walked away sends.
        $this->save($booking, [['full_name' => 'Ana Quispe']], false)->assertOk();

        $this->assertSame(1, $booking->travelers()->count());
    }

    public function test_the_submit_is_refused_when_the_leader_is_missing_a_field(): void
    {
        $tour = $this->tour(1, ['nationality']);
        $booking = $this->booking($tour, 1);

        $res = $this->save($booking, [['full_name' => 'Ana Quispe']], true);

        $res->assertStatus(422)->assertJsonPath('missing.field', 'nationality');
        // Nothing was written: the old travelers must survive a rejected submit.
        $this->assertSame(0, $booking->travelers()->count());
    }

    public function test_todos_los_pasajeros_refuses_fewer_travelers_than_seats(): void
    {
        $tour = $this->tour(2, ['nationality']);
        $booking = $this->booking($tour, 2);

        $res = $this->save($booking, [
            ['full_name' => 'Ana Quispe', 'nationality' => 'PE'],
        ], true);

        $res->assertStatus(422)->assertJsonPath('missing.traveler', 2);
    }

    public function test_todos_los_pasajeros_asks_the_second_passenger_too(): void
    {
        $tour = $this->tour(2, ['nationality'], ['hotel_name']);
        $booking = $this->booking($tour, 2);

        $res = $this->save($booking, [
            ['full_name' => 'Ana Quispe', 'nationality' => 'PE', 'extra_data' => ['hotel_name' => 'Casa Andina']],
            ['full_name' => 'Luis Mamani', 'nationality' => 'PE'],
        ], true);

        $res->assertStatus(422)
            ->assertJsonPath('missing.traveler', 2)
            ->assertJsonPath('missing.field', 'hotel_name');
    }

    public function test_solo_lider_does_not_ask_the_other_passengers(): void
    {
        $tour = $this->tour(1, ['nationality'], ['hotel_name']);
        $booking = $this->booking($tour, 2);

        $this->save($booking, [
            ['full_name' => 'Ana Quispe', 'nationality' => 'PE', 'extra_data' => ['hotel_name' => 'Casa Andina']],
            ['full_name' => 'Luis Mamani'],
        ], true)->assertOk();

        $this->assertSame(2, $booking->travelers()->count());
    }

    public function test_a_complete_submission_goes_through(): void
    {
        $tour = $this->tour(2, ['nationality', 'birthdate'], ['hotel_name']);
        $booking = $this->booking($tour, 2);

        $completo = fn (string $name) => [
            'full_name' => $name,
            'nationality' => 'PE',
            'extra_data' => ['birthdate' => '1990-01-01', 'hotel_name' => 'Casa Andina'],
        ];

        $this->save($booking, [$completo('Ana Quispe'), $completo('Luis Mamani')], true)->assertOk();

        $this->assertSame(2, $booking->travelers()->count());
        $this->assertTrue($booking->travelers()->where('is_leader', true)->exists());
    }

    public function test_name_parts_are_not_asked_twice(): void
    {
        // first_name/last_name are what the wizard calls the name it already
        // collects as full_name; asking for them again would be unanswerable.
        $tour = $this->tour(1, ['first_name', 'last_name']);
        $booking = $this->booking($tour, 1);

        $this->save($booking, [['full_name' => 'Ana Quispe']], true)->assertOk();
    }
}
