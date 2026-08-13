<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

/**
 * Warns the reservations desk that tours are about to stop being bookable.
 *
 * Deliberately NOT ShouldQueue, unlike the other mailables here: this is sent
 * from a scheduled command, so there is no request to keep fast, and queueing
 * it would mean the warning silently waits for a worker that may not be
 * running.
 */
class TourExpiryAlertMail extends Mailable
{
    public function __construct(
        /** @var Collection<int, array{tour: \App\Models\Tour, days_left: int}> */
        public Collection $expiring,
        /** @var Collection<int, array{tour: \App\Models\Tour, days_overdue: int}> */
        public Collection $backlog,
    ) {
    }

    public function envelope(): Envelope
    {
        $parts = [];
        if ($this->expiring->isNotEmpty()) {
            $soonest = $this->expiring->first()['days_left'] ?? null;
            $parts[] = $soonest !== null
                ? "{$this->expiring->count()} por caducar (el más próximo en {$soonest} días)"
                : "{$this->expiring->count()} por caducar";
        }
        if ($this->backlog->isNotEmpty()) {
            $parts[] = "{$this->backlog->count()} ya caducados";
        }

        return new Envelope(
            subject: 'Disponibilidad de tours · ' . (implode(' · ', $parts) ?: 'sin novedades'),
            from: new Address('reservas@incalake.com', 'Inca Lake'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tour-expiry-alert');
    }
}
