<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\ServiceDetail;
use App\Models\Payment;
use App\Models\InformationGroup;
use App\Models\BookingInformation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class BookingService
{
    public function create(array $bookingData): Booking
    {
        DB::beginTransaction();
        try {
            $booking = Booking::create([
                'code' => $this->generateBookingCode(),
                'created_at_booking' => now(),
            ]);

            $informationGroup = InformationGroup::create([
                'group_code' => $this->generateGroupCode(),
                'group_date' => now(),
            ]);

            $bookingDetail = BookingDetail::create([
                'email' => $bookingData['email'],
                'phone' => $bookingData['phone'] ?? null,
                'leader_name' => $bookingData['leader_name'],
                'booking_id' => $booking->id,
                'information_group_id' => $informationGroup->id,
            ]);

            if (isset($bookingData['services'])) {
                foreach ($bookingData['services'] as $service) {
                    $serviceDetail = ServiceDetail::create([
                        'service_date' => $service['date'],
                        'quantity' => $service['quantity'],
                        'total_price' => $service['total_price'],
                        'discount' => $service['discount'] ?? 0,
                        'product_id' => $service['product_id'],
                    ]);

                    $bookingDetail->services()->attach($serviceDetail->id);
                }
            }

            if (isset($bookingData['informations'])) {
                foreach ($bookingData['informations'] as $info) {
                    BookingInformation::create([
                        'information_value' => $info['value'],
                        'form_field_id' => $info['form_field_id'],
                        'information_group_id' => $informationGroup->id,
                    ]);
                }
            }

            if (isset($bookingData['payment'])) {
                $payment = Payment::create([
                    'amount' => $bookingData['payment']['amount'],
                    'description' => $bookingData['payment']['description'] ?? 'Payment for booking',
                ]);

                $bookingDetail->payments()->attach($payment->id);
            }

            DB::commit();
            Log::info("Booking created successfully", ['booking_id' => $booking->id, 'code' => $booking->code]);

            return $booking->load(['bookingDetails', 'bookingDetails.services']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error creating booking", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Booking $booking, array $bookingData): Booking
    {
        DB::beginTransaction();
        try {
            $booking->load(['bookingDetails']);

            foreach ($booking->bookingDetails as $detail) {
                $detail->update([
                    'email' => $bookingData['email'],
                    'phone' => $bookingData['phone'] ?? $detail->phone,
                    'leader_name' => $bookingData['leader_name'],
                ]);

                if (isset($bookingData['informations'])) {
                    $detail->informationGroup->bookingInformations()->delete();
                    foreach ($bookingData['informations'] as $info) {
                        BookingInformation::create([
                            'information_value' => $info['value'],
                            'form_field_id' => $info['form_field_id'],
                            'information_group_id' => $detail->information_group_id,
                        ]);
                    }
                }
            }

            DB::commit();
            Log::info("Booking updated successfully", ['booking_id' => $booking->id]);

            return $booking->refresh()->load(['bookingDetails']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error updating booking", ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(Booking $booking): bool
    {
        try {
            $booking->delete();
            Log::info("Booking deleted successfully", ['booking_id' => $booking->id]);
            return true;
        } catch (Exception $e) {
            Log::error("Error deleting booking", ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function generateBookingCode(): string
    {
        $code = 'BK' . date('Ymd') . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);

        while (Booking::where('code', $code)->exists()) {
            $code = 'BK' . date('Ymd') . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        }

        return $code;
    }

    protected function generateGroupCode(): string
    {
        $code = 'GRP' . strtoupper(uniqid());

        while (InformationGroup::where('group_code', $code)->exists()) {
            $code = 'GRP' . strtoupper(uniqid());
        }

        return $code;
    }

    public function getBookingByCode(string $code): ?Booking
    {
        return Booking::where('code', $code)->first();
    }

    public function calculateTotalPrice(Booking $booking): float
    {
        $total = 0;

        foreach ($booking->bookingDetails as $detail) {
            foreach ($detail->services as $service) {
                $total += $service->total_price - $service->discount;
            }
        }

        return $total;
    }
}