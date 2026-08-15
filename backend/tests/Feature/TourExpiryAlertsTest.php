<?php

namespace Tests\Feature;

use App\Mail\TourExpiryAlertMail;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The failure modes here are both bad and both silent: warn about nothing and
 * tours quietly stop selling, or warn about everything every fortnight and the
 * mail gets filtered. 133 of the 290 live tours are already expired, so the
 * repeat rules are what make this usable at all.
 */
class TourExpiryAlertsTest extends TestCase
{
    use RefreshDatabase;

    private function tourEnding(string $end, array $extra = []): Tour
    {
        return Tour::factory()->create(array_merge([
            'status' => 'published',
            'availability_data' => ['start' => '2020-01-01', 'end' => $end],
        ], $extra));
    }

    public function test_warns_about_a_tour_inside_the_three_month_window(): void
    {
        Mail::fake();
        $tour = $this->tourEnding(now()->addDays(80)->toDateString());

        $this->artisan('tours:expiry-alerts')->assertExitCode(0);

        Mail::assertSent(TourExpiryAlertMail::class, fn ($m) => $m->expiring->contains(
            fn ($row) => $row['tour']->id === $tour->id
        ));
        $this->assertNotNull($tour->fresh()->expiry_alert_sent_at);
    }

    public function test_ignores_a_tour_still_far_from_its_date(): void
    {
        Mail::fake();
        $this->tourEnding(now()->addDays(200)->toDateString());

        $this->artisan('tours:expiry-alerts')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_does_not_repeat_within_fifteen_days(): void
    {
        Mail::fake();
        $this->tourEnding(now()->addDays(60)->toDateString(), [
            'expiry_alert_sent_at' => now()->subDays(3),
        ]);

        $this->artisan('tours:expiry-alerts')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_repeats_once_fifteen_days_have_passed(): void
    {
        Mail::fake();
        $this->tourEnding(now()->addDays(60)->toDateString(), [
            'expiry_alert_sent_at' => now()->subDays(16),
        ]);

        $this->artisan('tours:expiry-alerts')->assertExitCode(0);

        Mail::assertSent(TourExpiryAlertMail::class);
    }

    public function test_already_expired_tours_are_reported_once_and_never_again(): void
    {
        Mail::fake();
        $tour = $this->tourEnding('2019-12-31');

        $this->artisan('tours:expiry-alerts')->assertExitCode(0);
        Mail::assertSent(TourExpiryAlertMail::class, fn ($m) => $m->backlog->contains(
            fn ($row) => $row['tour']->id === $tour->id
        ));

        // Second run: the backlog has been reported, so it stays quiet even
        // though the tour is still expired. Otherwise 133 tours would repeat
        // every fortnight forever.
        Mail::fake();
        $this->artisan('tours:expiry-alerts')->assertExitCode(0);
        Mail::assertNothingSent();
    }

    public function test_a_tour_marked_as_never_expiring_is_ignored(): void
    {
        Mail::fake();
        Tour::factory()->create([
            'status' => 'published',
            'availability_data' => ['end' => '2019-12-31', 'neverExpires' => true],
        ]);

        $this->artisan('tours:expiry-alerts')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_tours_without_an_end_date_are_ignored(): void
    {
        Mail::fake();
        Tour::factory()->create([
            'status' => 'published',
            'availability_data' => ['start' => '2020-01-01', 'end' => ''],
        ]);

        $this->artisan('tours:expiry-alerts')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_unpublished_tours_are_ignored(): void
    {
        // Nobody can book a draft, so its calendar closing is not news.
        Mail::fake();
        $this->tourEnding(now()->addDays(10)->toDateString(), ['status' => 'draft']);

        $this->artisan('tours:expiry-alerts')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_dry_run_sends_nothing_and_marks_nothing(): void
    {
        Mail::fake();
        $tour = $this->tourEnding(now()->addDays(30)->toDateString());

        $this->artisan('tours:expiry-alerts', ['--dry-run' => true])->assertExitCode(0);

        Mail::assertNothingSent();
        $this->assertNull($tour->fresh()->expiry_alert_sent_at);
    }

    public function test_the_email_actually_renders(): void
    {
        // Mail::fake() never renders the view, so every other test here passed
        // while the template threw on a bad optional() call. Nothing would have
        // been sent and the job would just have gone red.
        $soon = $this->tourEnding(now()->addDays(12)->toDateString());
        $old = $this->tourEnding('2019-12-31');

        $mail = new \App\Mail\TourExpiryAlertMail(
            collect([['tour' => $soon, 'days_left' => 12]]),
            collect([['tour' => $old, 'days_overdue' => 2417]]),
        );

        $html = $mail->render();

        $this->assertStringContainsString($soon->code, $html);
        $this->assertStringContainsString($old->code, $html);
        // The link into step 8 is the whole point: it's what makes the warning
        // actionable instead of just informative.
        $this->assertStringContainsString("/admin/v2/tours/{$soon->id}/edit?step=7", $html);
        $this->assertStringContainsString('12 días', $html);
        $this->assertStringContainsString('2019-12-31', $html);
        $this->assertStringContainsString('por caducar', $mail->envelope()->subject);
    }

    public function test_nothing_is_marked_when_the_mail_fails(): void
    {
        // Marking before a confirmed send would drop the warning silently.
        $tour = $this->tourEnding(now()->addDays(30)->toDateString());
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp caido'));

        $this->artisan('tours:expiry-alerts')->assertExitCode(1);

        $this->assertNull($tour->fresh()->expiry_alert_sent_at);
    }
}
