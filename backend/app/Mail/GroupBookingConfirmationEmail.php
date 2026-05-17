<?php

namespace App\Mail;

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
    /** @var Collection<int,\App\Models\Booking> */
    public Collection $bookings;
    public bool $isAdminCopy;

    public function __construct(Collection $bookings, bool $isAdminCopy = false)
    {
        // Stable order: by tour date then code.
        $this->bookings = $bookings->sortBy([
            ['tour_date', 'asc'],
            ['booking_code', 'asc'],
        ])->values();
        $this->isAdminCopy = $isAdminCopy;
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

        return new Content(
            view: 'emails.booking-confirmation-group',
            with: [
                'bookings'      => $this->bookings,
                'primary'       => $this->bookings->first(),
                'isAdminCopy'   => $this->isAdminCopy,
                'currency'      => $currency,
                'groupSubtotal' => $this->bookings->sum(fn ($b) => (float) $b->subtotal),
                'groupTax'      => $this->bookings->sum(fn ($b) => (float) ($b->tax_amount ?? 0)),
                'groupDiscount' => $this->bookings->sum(fn ($b) => (float) ($b->discount ?? 0)),
                'groupTotal'    => $this->bookings->sum(fn ($b) => (float) $b->total),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
