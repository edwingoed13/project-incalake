<?php

namespace App\Mail;

use App\Models\AvailabilityInquiry;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Operator notification for a new availability request (tours that require
 * availability verification before booking). Sent to reservas@incalake.com.
 *
 * Sent synchronously, like every other mailable here. It used to declare
 * `implements ShouldQueue` + `use Queueable, SerializesModels` WITHOUT importing
 * any of the three, so PHP looked for App\Mail\Queueable and the class fataled
 * the moment it was instantiated — the caller's try/catch swallowed it and the
 * lead was stored but nobody was ever notified. Queueing would fail a second
 * time anyway: QUEUE_CONNECTION=database and no worker runs on the cPanel host.
 */
class AvailabilityInquiryMail extends Mailable
{
    public AvailabilityInquiry $inquiry;

    public function __construct(AvailabilityInquiry $inquiry)
    {
        $this->inquiry = $inquiry;
    }

    public function envelope(): Envelope
    {
        $tour = $this->inquiry->tour_title ?: 'Tour';
        return new Envelope(
            subject: "Consulta de disponibilidad · {$tour} · {$this->inquiry->name}",
            from: new Address('reservas@incalake.com', 'Inca Lake'),
            // Let staff reply straight to the customer.
            replyTo: [new Address($this->inquiry->email, $this->inquiry->name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.availability-inquiry',
            with: ['inquiry' => $this->inquiry],
        );
    }
}
