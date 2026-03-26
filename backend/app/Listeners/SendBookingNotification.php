<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Jobs\SendBookingConfirmation;
use Illuminate\Support\Facades\Log;

class SendBookingNotification
{
    public function handle(BookingCreated $event): void
    {
        try {
            Log::info("Sending booking notification", ['booking_id' => $event->booking->id]);

            SendBookingConfirmation::dispatch($event->booking);
        } catch (\Exception $e) {
            Log::error("Error sending booking notification", [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}