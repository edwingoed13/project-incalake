<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Operator notification for a message sent from the public contact form.
 * Sent to reservas@incalake.com with reply-to set to the customer, so staff
 * can answer straight from the inbox.
 */
class ContactMessageMail extends Mailable
{
    public function __construct(public ContactMessage $contact)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Mensaje de contacto · {$this->contact->name}",
            from: new Address('reservas@incalake.com', 'Inca Lake'),
            replyTo: [new Address($this->contact->email, $this->contact->name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-message',
            with: ['contact' => $this->contact],
        );
    }
}
