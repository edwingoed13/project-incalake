<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Mail\BookingConfirmationEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

class SendBookingConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Booking $booking;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        try {
            $bookingDetail = $this->booking->bookingDetails()->first();

            if (!$bookingDetail) {
                Log::warning("No booking detail found", ['booking_id' => $this->booking->id]);
                return;
            }

            Mail::to($bookingDetail->email)->send(new BookingConfirmationEmail($this->booking));

            Log::info("Booking confirmation sent successfully", [
                'booking_id' => $this->booking->id,
                'email' => $bookingDetail->email
            ]);
        } catch (Exception $e) {
            Log::error("Error sending booking confirmation", [
                'booking_id' => $this->booking->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error("Failed to send booking confirmation", [
            'booking_id' => $this->booking->id,
            'error' => $exception->getMessage()
        ]);
    }
}