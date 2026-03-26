<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingConfirmationEmail extends Mailable
{
    public Booking $booking;
    public bool $isAdminCopy;

    public function __construct(Booking $booking, bool $isAdminCopy = false)
    {
        $this->booking = $booking;
        $this->isAdminCopy = $isAdminCopy;
    }

    public function envelope(): Envelope
    {
        $subject = $this->isAdminCopy
            ? "Nueva Reserva Confirmada #{$this->booking->booking_code} - {$this->booking->customer_name}"
            : "Confirmacion de Reserva #{$this->booking->booking_code} - Inca Lake";

        return new Envelope(
            subject: $subject,
            from: new Address('reservas@incalake.com', 'Inca Lake')
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-confirmation',
            with: [
                'booking'     => $this->booking,
                'isAdminCopy' => $this->isAdminCopy,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
