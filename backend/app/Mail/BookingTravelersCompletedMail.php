<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Operator notification sent once the customer completes the per-passenger
 * data on the booking-confirmation page (Step "Viajeros" finalized). Recaps
 * traveler names, pickup choice, special requests, and links to the booking
 * in the admin so staff can prep the tour.
 */
class BookingTravelersCompletedMail extends Mailable
{
    public Booking $booking;
    public string $adminBookingUrl;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
        // Public admin booking listing; staff can search by booking_code. Kept
        // as a single URL (vs. deep-linking to an unknown internal id route)
        // so it never points at a 404 if the admin URL scheme changes.
        $this->adminBookingUrl = 'https://incalake-admin.vercel.app/admin/v2/bookings';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Datos de pasajeros · Reserva #{$this->booking->booking_code} · {$this->booking->customer_name}",
            from: new Address('reservas@incalake.com', 'Inca Lake')
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-travelers-completed',
            with: [
                'booking' => $this->booking,
                'adminUrl' => $this->adminBookingUrl,
            ]
        );
    }
}
