<?php

namespace App\Mail;

use App\Mail\Concerns\FormatsTourContent;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

/**
 * One consolidated confirmation email for a multi-tour cart paid in a single
 * Culqi charge. Replaces sending N separate per-booking emails.
 */
class GroupBookingConfirmationEmail extends Mailable
{
    use FormatsTourContent;

    /** @var Collection<int,\App\Models\Booking> */
    public Collection $bookings;
    public bool $isAdminCopy;
    /** Real amount charged/captured by the gateway for the whole group.
     *  Null = treat as fully paid (= group total). */
    public ?float $amountPaid;

    public function __construct(Collection $bookings, bool $isAdminCopy = false, ?float $amountPaid = null)
    {
        // Stable order: by tour date then code.
        $this->bookings = $bookings->sortBy([
            ['tour_date', 'asc'],
            ['booking_code', 'asc'],
        ])->values();
        $this->isAdminCopy = $isAdminCopy;
        $this->amountPaid = $amountPaid;
    }

    public function envelope(): Envelope
    {
        $primary = $this->bookings->first();
        $count = $this->bookings->count();

        $subject = $this->isAdminCopy
            ? "Nueva Reserva ({$count} tours) #{$primary->booking_code} - {$primary->customer_name}"
            : "Confirmacion de Reserva ({$count} tours) #{$primary->booking_code} - Inca Lake";

        return new Envelope(
            subject: $subject,
            from: new Address('reservas@incalake.com', 'Inca Lake')
        );
    }

    public function content(): Content
    {
        $currency = $this->bookings->first()->currency;
        $groupTotal = round($this->bookings->sum(fn ($b) => (float) $b->total), 2);
        $groupPaid = round($this->amountPaid ?? $groupTotal, 2);
        $groupRemaining = round(max(0, $groupTotal - $groupPaid), 2);

        // Per-tour includes/excludes, keyed by booking id (the view loops the
        // same collection).
        $tourLists = [];
        foreach ($this->bookings as $b) {
            $tourLists[$b->id] = $this->tourIncludeLists($b->tour);
        }

        return new Content(
            view: 'emails.booking-confirmation-group',
            with: [
                'bookings'       => $this->bookings,
                'primary'        => $this->bookings->first(),
                'isAdminCopy'    => $this->isAdminCopy,
                'tourLists'      => $tourLists,
                'currency'       => $currency,
                'groupSubtotal'  => $this->bookings->sum(fn ($b) => (float) $b->subtotal),
                'groupTax'       => $this->bookings->sum(fn ($b) => (float) ($b->tax_amount ?? 0)),
                'groupDiscount'  => $this->bookings->sum(fn ($b) => (float) ($b->discount ?? 0)),
                'groupTotal'     => $groupTotal,
                'groupPaid'      => $groupPaid,
                'groupRemaining' => $groupRemaining,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
