<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterSignupTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_a_subscriber(): void
    {
        $this->postJson('/api/newsletter', ['email' => 'Ana@Example.com', 'language' => 'es'])
            ->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email'  => 'ana@example.com',   // normalised
            'source' => 'footer',
        ]);
    }

    /** Signing up twice is not an error, and it clears a previous opt-out. */
    public function test_signing_up_twice_is_idempotent_and_resubscribes(): void
    {
        NewsletterSubscriber::create([
            'email' => 'ana@example.com',
            'unsubscribed_at' => now(),
        ]);

        $this->postJson('/api/newsletter', ['email' => 'ana@example.com'])->assertCreated();

        $this->assertDatabaseCount('newsletter_subscribers', 1);
        $this->assertNull(NewsletterSubscriber::first()->unsubscribed_at);
    }

    public function test_it_rejects_an_invalid_email(): void
    {
        $this->postJson('/api/newsletter', ['email' => 'no-es-un-correo'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }
}
