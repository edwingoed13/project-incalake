<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

/**
 * Cancels abandoned checkouts: bookings that were created (a BK- code was
 * generated) but never paid. Booking codes are created before payment so they
 * can be referenced by the payment gateway; this command keeps those unpaid
 * "pending" rows from lingering forever.
 */
class ExpirePendingBookings extends Command
{
    protected $signature = 'bookings:expire-pending {--hours=24}';

    protected $description = 'Cancel unpaid pending bookings older than N hours (abandoned checkouts).';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours);

        $bookings = Booking::where('payment_status', 'pending')
            ->where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->get();

        if ($bookings->isEmpty()) {
            $this->info("No unpaid pending bookings older than {$hours}h.");
            return self::SUCCESS;
        }

        foreach ($bookings as $booking) {
            $booking->cancel("Auto-expirada: checkout sin pago tras {$hours}h");
        }

        $this->info("Cancelled {$bookings->count()} abandoned pending booking(s) older than {$hours}h.");
        return self::SUCCESS;
    }
}
