<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Services\BookingService;
use App\Services\PriceCalculatorService;
use App\Models\Tour;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingProcessTest extends TestCase
{
    use RefreshDatabase;

    protected BookingService $bookingService;
    protected PriceCalculatorService $priceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = app(BookingService::class);
        $this->priceService = app(PriceCalculatorService::class);
    }

    public function test_can_create_booking(): void
    {
        $tour = Tour::factory()->hasPrices(2)->create();

        $bookingData = [
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'leader_name' => 'John Doe',
            'services' => [
                [
                    'product_id' => $tour->id,
                    'date' => now()->addWeek()->toDateString(),
                    'quantity' => 2,
                    'total_price' => 200,
                ],
            ],
            'payment' => [
                'amount' => 200,
                'description' => 'Payment for tour',
            ],
        ];

        $booking = $this->bookingService->create($bookingData);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
        $this->assertDatabaseHas('booking_details', ['email' => 'test@example.com']);
        $this->assertStringContainsString('BK', $booking->code);
    }

    public function test_can_update_booking(): void
    {
        $tour = Tour::factory()->hasPrices(2)->create();

        $booking = Booking::factory()->create();

        $updateData = [
            'email' => 'updated@example.com',
            'phone' => '+9876543210',
            'leader_name' => 'Jane Doe',
        ];

        $updatedBooking = $this->bookingService->update($booking, $updateData);

        $this->assertEquals('updated@example.com', $updatedBooking->bookingDetails->first()->email);
    }

    public function test_can_delete_booking(): void
    {
        $booking = Booking::factory()->create();

        $result = $this->bookingService->delete($booking);

        $this->assertTrue($result);
        $this->assertSoftDeleted('bookings', ['id' => $booking->id]);
    }

    public function test_calculate_price_with_participants(): void
    {
        $tour = Tour::factory()->hasPrices(2)->create();

        $participants = [
            ['age_stage_id' => $tour->prices->first()->age_stage_id, 'quantity' => 2],
        ];

        $price = $this->priceService->calculateTourPrice($tour, $participants, null, now()->toDateString());

        $this->assertArrayHasKey('subtotal', $price);
        $this->assertArrayHasKey('total', $price);
        $this->assertArrayHasKey('details', $price);
        $this->assertGreaterThan(0, $price['total']);
    }

    public function test_calculate_price_with_coupon(): void
    {
        $tour = Tour::factory()->hasPrices(2)->create();

        $participants = [
            ['age_stage_id' => $tour->prices->first()->age_stage_id, 'quantity' => 1],
        ];

        $price = $this->priceService->calculateTourPrice($tour, $participants);

        $priceWithCoupon = $this->priceService->calculateTourPrice($tour, $participants, 'TEST20');

        $this->assertLessThan($price['total'], $priceWithCoupon['total']);
    }

    public function test_get_booking_by_code(): void
    {
        $booking = Booking::factory()->create(['code' => 'BK2026012700001']);

        $foundBooking = $this->bookingService->getBookingByCode($booking->code);

        $this->assertEquals($booking->id, $foundBooking->id);
    }

    public function test_calculate_total_price(): void
    {
        $booking = Booking::factory()->create();

        $total = $this->bookingService->calculateTotalPrice($booking);

        $this->assertIsFloat($total);
    }

    public function test_can_validate_coupon(): void
    {
        $result = $this->priceService->validateCoupon('INVALID_CODE');

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_booking_has_unique_code(): void
    {
        $booking1 = Booking::factory()->create();
        $booking2 = Booking::factory()->create();

        $this->assertNotEquals($booking1->code, $booking2->code);
    }
}