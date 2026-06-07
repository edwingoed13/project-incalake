<?php

namespace App\Jobs;

use App\Mail\BookingConfirmationEmail;
use App\Mail\GroupBookingConfirmationEmail;
use App\Models\Booking;
use App\Services\GoogleCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Post-payment side-effects: Google Calendar event + customer email + admin
 * email. Runs on the queue so the HTTP request that confirmed the charge can
 * return immediately. The user's browser shows the booking-confirmation page
 * in <500ms; the email lands 5-30s later, the calendar event ~1-2s after.
 *
 * Why one job for everything (not three):
 *   - The same data dependency (the group of paid bookings); splitting forces
 *     three lookups or three serialized payloads.
 *   - Calendar failure shouldn't block emails (they're for two different
 *     audiences). Each side-effect is wrapped in its own try/catch and logged
 *     independently — the job as a whole only fails if ALL three throw
 *     (extremely unlikely).
 *   - Retry semantics: a 5xx from SMTP retries, but Google Calendar 4xx
 *     (e.g. invalid token) shouldn't keep retrying forever. Per-side-effect
 *     try/catch + finite $tries on the job covers both.
 *
 * Idempotency:
 *   - markAsPaid was already called in the controller (sync, atomic).
 *   - Re-running this job re-sends emails + re-adds calendar event. That's
 *     accepted: SMTP retries are visible (the customer sees 2 mails) but
 *     never lose the confirmation. Adding a payment_data.confirmation_sent_at
 *     gate would be safer; deferred to follow-up if the SMTP retry log
 *     becomes noisy.
 */
class SendPaymentConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maximum attempts before the job lands in failed_jobs. Queue:work
     *  applies a 3-attempt default; making it explicit here so a future
     *  cron command change doesn't silently increase retries. */
    public int $tries = 3;

    /** Seconds before the next attempt. Spread out so SMTP/Calendar
     *  transient errors get a chance to clear. */
    public int $backoff = 30;

    /**
     * @param array<int> $bookingIds  the group that was just paid
     * @param string     $paymentMethod 'culqi' | 'paypal'
     * @param float      $paidNow      amount paid in tour currency (not cents)
     */
    public function __construct(
        public array $bookingIds,
        public string $paymentMethod,
        public float $paidNow,
    ) {}

    public function handle(): void
    {
        $bookings = Booking::with([
                'tour',
                'tour.translations.language',
                'tour.mediaGallery',
                'travelers',
                'pickupDetail',
            ])
            ->whereIn('id', $this->bookingIds)
            ->get();

        if ($bookings->isEmpty()) {
            Log::warning('SendPaymentConfirmationJob: no bookings found', [
                'booking_ids' => $this->bookingIds,
            ]);
            return;
        }

        // 1) Google Calendar per booking — independent failures.
        foreach ($bookings as $b) {
            try {
                (new GoogleCalendarService())->createBookingEvent([
                    'booking_code'   => $b->booking_code,
                    'tour_title'     => $b->tour_title,
                    'tour_date'      => \Carbon\Carbon::parse($b->tour_date)->format('Y-m-d'),
                    'tour_time'      => \Carbon\Carbon::parse($b->tour_time)->format('H:i:s'),
                    'adults'         => $b->adults,
                    'children'       => $b->children,
                    'customer_name'  => $b->customer_name,
                    'customer_email' => $b->customer_email,
                    'customer_phone' => $b->customer_phone,
                    'total'          => $b->total,
                    'currency'       => $b->currency,
                    'payment_method' => $this->paymentMethod,
                ]);
            } catch (Throwable $e) {
                Log::error('Calendar event failed (job)', [
                    'booking_id' => $b->id,
                    'gateway' => $this->paymentMethod,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 2) Confirmation emails — one for customer + one for admin.
        try {
            if ($bookings->count() === 1) {
                $single = $bookings->first();
                Mail::to($single->customer_email)->send(new BookingConfirmationEmail($single, false, $this->paidNow));
                Mail::to('reservas@incalake.com')->send(new BookingConfirmationEmail($single, true, $this->paidNow));
            } else {
                Mail::to($bookings->first()->customer_email)->send(new GroupBookingConfirmationEmail($bookings, false, $this->paidNow));
                Mail::to('reservas@incalake.com')->send(new GroupBookingConfirmationEmail($bookings, true, $this->paidNow));
            }
            Log::info('Confirmation emails sent (job)', [
                'group_size' => $bookings->count(),
                'booking_ids' => $this->bookingIds,
                'gateway' => $this->paymentMethod,
            ]);
        } catch (Throwable $e) {
            // Re-throw so the queue worker counts this as a failed attempt
            // and retries. Calendar errors don't re-throw because they're
            // operator-only; emails ARE customer-facing.
            Log::error('Confirmation emails failed (job)', [
                'booking_ids' => $this->bookingIds,
                'gateway' => $this->paymentMethod,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /** Final landing zone when all retries are exhausted. */
    public function failed(Throwable $e): void
    {
        Log::error('SendPaymentConfirmationJob exhausted retries', [
            'booking_ids' => $this->bookingIds,
            'gateway' => $this->paymentMethod,
            'error' => $e->getMessage(),
        ]);
    }
}
