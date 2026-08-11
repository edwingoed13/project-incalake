<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $adults = $this->faker->numberBetween(1, 4);
        $children = $this->faker->numberBetween(0, 3);
        $subtotal = $this->faker->randomFloat(2, 50, 800);

        return [
            'booking_code' => 'BK-' . strtoupper($this->faker->unique()->bothify('####-????')),
            'tour_id' => Tour::factory(),
            // Snapshotted at booking time, so it is a plain string rather than
            // a lookup — a later title change must not rewrite past bookings.
            'tour_title' => $this->faker->sentence(3),
            'tour_date' => $this->faker->dateTimeBetween('+1 day', '+3 months')->format('Y-m-d'),
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'customer_phone' => $this->faker->phoneNumber(),
            'adults' => $adults,
            'children' => $children,
            'infants' => 0,
            'total_participants' => $adults + $children,
            'currency' => 'USD',
            'subtotal' => $subtotal,
            'discount' => 0,
            'total' => $subtotal,
            'payment_method' => 'culqi',
            'payment_status' => 'pending',
            'status' => 'pending',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'payment_status' => 'paid',
            'status' => 'confirmed',
            'paid_at' => now(),
        ]);
    }
}
