<?php

namespace Tests\Feature;

use App\Mail\AvailabilityInquiryMail;
use App\Mail\BookingTravelersCompletedMail;
use App\Mail\ContactMessageMail;
use App\Models\AvailabilityInquiry;
use App\Models\Booking;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Everything here guards the same failure: a customer writes to Incalake and
 * nobody ever finds out. Two of these mailables shipped declaring
 * `implements ShouldQueue` + `use Queueable, SerializesModels` without
 * importing any of them, so PHP resolved App\Mail\Queueable, the constructor
 * fataled, and the controllers' try/catch logged it and returned success. The
 * contact form was worse still — it never called the API at all.
 *
 * Note the two kinds of assertion below: Mail::assertSent proves the mailable
 * can be CONSTRUCTED (the bug above), while ->render() proves the Blade view
 * actually compiles — a faked mail never renders, which is how a broken
 * template hid in the expiry alerts before.
 */
class ContactAndInquiryMailTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload = [
        'name'     => 'Ana Quispe',
        'email'    => 'ana@example.com',
        'phone'    => '+51 982 769 453',
        'message'  => "Hola, quiero información del tour a Uros.\nGracias.",
        'language' => 'es',
    ];

    public function test_contact_form_stores_the_message_and_emails_reservations(): void
    {
        Mail::fake();

        $this->postJson('/api/contact', $this->validPayload)
            ->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('contact_messages', [
            'email'  => 'ana@example.com',
            'status' => 'new',
        ]);

        Mail::assertSent(ContactMessageMail::class, fn ($mail) => $mail->hasTo(config('services.incalake.reservations_email'))
            && $mail->contact->name === 'Ana Quispe');
    }

    public function test_contact_form_rejects_an_incomplete_submission(): void
    {
        Mail::fake();

        $this->postJson('/api/contact', ['name' => 'Ana', 'email' => 'no-es-un-correo'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'message']);

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingSent();
    }

    /** The message must survive even when the mail transport is down. */
    public function test_contact_message_is_kept_when_the_email_fails(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->postJson('/api/contact', $this->validPayload)->assertCreated();

        $this->assertDatabaseHas('contact_messages', ['email' => 'ana@example.com']);
    }

    public function test_contact_email_renders(): void
    {
        $contact = ContactMessage::create($this->validPayload + ['status' => 'new']);

        $html = (new ContactMessageMail($contact))->render();

        $this->assertStringContainsString('Ana Quispe', $html);
        $this->assertStringContainsString('quiero información del tour a Uros', $html);
    }

    /** Regression: this mailable used to fatal the moment it was constructed. */
    public function test_availability_inquiry_email_can_be_built_and_rendered(): void
    {
        $inquiry = AvailabilityInquiry::create([
            'tour_title' => 'Tour a Los Uros',
            'name'       => 'Ana Quispe',
            'email'      => 'ana@example.com',
            'adults'     => 2,
            'children'   => 0,
            'status'     => 'new',
        ]);

        $html = (new AvailabilityInquiryMail($inquiry))->render();

        $this->assertStringContainsString('Tour a Los Uros', $html);
        $this->assertStringContainsString('Ana Quispe', $html);
    }

    /** Same regression, second mailable. */
    public function test_travelers_completed_email_can_be_built(): void
    {
        $booking = Booking::factory()->create();

        $mail = new BookingTravelersCompletedMail($booking);

        $this->assertSame($booking->id, $mail->booking->id);
        $this->assertStringContainsString('/admin/v2/bookings', $mail->adminBookingUrl);
    }
}
