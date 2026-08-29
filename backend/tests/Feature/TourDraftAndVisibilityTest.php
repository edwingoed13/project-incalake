<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\TourRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Locks in the two promises made when the draft buffer shipped:
 *
 *   1. Editing a PUBLISHED tour must not change what the public sees until
 *      someone explicitly publishes. Before this, autosave shipped the tour's
 *      current status, so half-finished edits reached the live site ~2s after
 *      typing.
 *   2. An unpublished tour must not be reachable at all. The listing only
 *      filtered by status when ?status= was passed, so drafts were served to
 *      anyone hitting the API.
 *
 * Both are the kind of thing that regresses silently — nothing about a normal
 * refactor of TourController or TourService would obviously break them.
 */
class TourDraftAndVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    // --- Draft buffer -----------------------------------------------------

    public function test_saving_a_draft_does_not_touch_the_live_tour(): void
    {
        $tour = Tour::factory()->create(['capacity' => 30]);
        Sanctum::actingAs($this->admin());

        $response = $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'schema_version' => 'v1',
            'payload' => ['basicInfo' => ['title' => 'Cambiado', 'capacityMax' => 99]],
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        // The whole point: the live row is untouched.
        $this->assertSame(30, $tour->fresh()->capacity);
        $this->assertDatabaseHas('tour_revisions', ['tour_id' => $tour->id]);
    }

    public function test_draft_is_returned_to_the_editor(): void
    {
        $tour = Tour::factory()->create();
        Sanctum::actingAs($this->admin());

        $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'schema_version' => 'v1',
            'payload' => ['basicInfo' => ['title' => 'Borrador pendiente']],
        ])->assertOk();

        $this->getJson("/api/admin/tours/{$tour->id}/revision?schema_version=v1")
            ->assertOk()
            ->assertJsonPath('data.payload.basicInfo.title', 'Borrador pendiente');
    }

    public function test_a_draft_from_an_older_editor_version_is_not_applied(): void
    {
        $tour = Tour::factory()->create();
        TourRevision::create([
            'tour_id' => $tour->id,
            'payload' => ['basicInfo' => ['title' => 'forma antigua']],
            'schema_version' => 'v0',
        ]);
        Sanctum::actingAs($this->admin());

        // Restoring a v0 payload into a v1 wizard would put values in fields
        // that no longer mean the same thing, so it is not handed back.
        $this->getJson("/api/admin/tours/{$tour->id}/revision?schema_version=v1")
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('stale', true);

        // It used to be DELETED here, and this test asserted that. Not applying
        // it is right; destroying it on a read is not. Any caller that gets the
        // parameter wrong — a typo, an older tab, a script — wiped an
        // operator's unpublished work and got a 200 for it, with no undo behind
        // it. Removing a draft is what DELETE is for.
        $this->assertDatabaseHas('tour_revisions', ['tour_id' => $tour->id]);
    }

    /** The read path must never be the thing that loses someone's work. */
    public function test_reading_with_the_wrong_schema_version_leaves_the_draft_intact(): void
    {
        $tour = Tour::factory()->create();
        Sanctum::actingAs($this->admin());

        $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'schema_version' => 'v1',
            'payload' => ['basicInfo' => ['title' => 'trabajo sin publicar']],
        ])->assertOk();

        // '1' instead of 'v1' — the exact typo that cost a real draft.
        $this->getJson("/api/admin/tours/{$tour->id}/revision?schema_version=1")
            ->assertOk()
            ->assertJsonPath('data', null);

        // And it is still there for the next correct read.
        $this->getJson("/api/admin/tours/{$tour->id}/revision?schema_version=v1")
            ->assertOk()
            ->assertJsonPath('data.payload.basicInfo.title', 'trabajo sin publicar');
    }

    public function test_discarding_removes_the_draft(): void
    {
        $tour = Tour::factory()->create();
        Sanctum::actingAs($this->admin());

        $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'schema_version' => 'v1',
            'payload' => ['basicInfo' => ['title' => 'x']],
        ])->assertOk();

        // POST, not DELETE: mod_security on the production host blocks DELETE.
        $this->postJson("/api/admin/tours/{$tour->id}/revision/delete")->assertOk();

        $this->assertDatabaseMissing('tour_revisions', ['tour_id' => $tour->id]);
    }

    public function test_drafts_are_admin_only(): void
    {
        $tour = Tour::factory()->create();

        $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'payload' => ['basicInfo' => []],
        ])->assertUnauthorized();

        Sanctum::actingAs($this->customer());
        $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'payload' => ['basicInfo' => []],
        ])->assertForbidden();
    }

    public function test_draft_payload_must_be_an_object(): void
    {
        $tour = Tour::factory()->create();
        Sanctum::actingAs($this->admin());

        $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'payload' => 'no soy un objeto',
        ])->assertStatus(422);
    }

    // --- Concurrent editors -----------------------------------------------

    public function test_a_save_built_on_a_stale_version_is_rejected(): void
    {
        $tour = Tour::factory()->create();
        $ana = User::factory()->create(['role' => 'admin', 'name' => 'Ana']);
        $beto = User::factory()->create(['role' => 'admin', 'name' => 'Beto']);

        // Both operators open the tour and read version 1.
        Sanctum::actingAs($ana);
        $version = $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'payload' => ['basicInfo' => ['title' => 'de Ana']],
        ])->json('data.version');

        // Beto saves on top, so the stored draft moves to version 2.
        Sanctum::actingAs($beto);
        $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'payload' => ['basicInfo' => ['title' => 'de Beto']],
            'base_version' => $version,
            'tab_id' => 'tab-de-beto',
        ])->assertOk();

        // Ana, still holding version 1, would silently erase Beto's work. The
        // 409 carries the winner's tab id so the wizard can tell a same-tab
        // race (auto-resolve) from a genuinely different editor.
        Sanctum::actingAs($ana);
        $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'payload' => ['basicInfo' => ['title' => 'de Ana otra vez']],
            'base_version' => $version,
            'tab_id' => 'tab-de-ana',
        ])
            ->assertStatus(409)
            ->assertJsonPath('conflict', true)
            ->assertJsonPath('data.updated_by_name', 'Beto')
            ->assertJsonPath('data.updated_by_tab', 'tab-de-beto');

        // Beto's draft is intact.
        $this->assertSame(
            'de Beto',
            TourRevision::where('tour_id', $tour->id)->first()->payload['basicInfo']['title']
        );
    }

    public function test_saving_with_the_current_version_succeeds(): void
    {
        $tour = Tour::factory()->create();
        Sanctum::actingAs($this->admin());

        $v1 = $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'payload' => ['basicInfo' => ['title' => 'uno']],
        ])->json('data.version');

        $v2 = $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'payload' => ['basicInfo' => ['title' => 'dos']],
            'base_version' => $v1,
        ])->assertOk()->json('data.version');

        $this->assertSame($v1 + 1, $v2);
    }

    public function test_omitting_base_version_still_saves(): void
    {
        // An admin build older than this change sends no base_version. It must
        // keep working rather than start 409-ing on every autosave.
        $tour = Tour::factory()->create();
        Sanctum::actingAs($this->admin());

        $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'payload' => ['basicInfo' => ['title' => 'uno']],
        ])->assertOk();

        $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'payload' => ['basicInfo' => ['title' => 'dos']],
        ])->assertOk();
    }

    // --- What the listing reports about a pending draft --------------------

    public function test_listing_reports_when_the_pending_draft_was_saved(): void
    {
        $tour = Tour::factory()->create();
        Sanctum::actingAs($this->admin());

        $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'schema_version' => 'v1',
            'payload' => ['basicInfo' => ['title' => 'Parked']],
        ])->assertOk();

        $row = collect($this->getJson('/api/tours')->json('data'))
            ->firstWhere('id', $tour->id);

        $this->assertTrue($row['has_pending_draft']);

        // The flag alone cannot tell an operator whether this is their own
        // unfinished edit or something a colleague left weeks ago, which is
        // the whole reason the timestamp rides along.
        $this->assertArrayHasKey('pending_draft_at', $row);
        $this->assertNotNull($row['pending_draft_at']);

        // It must arrive as a date the browser can actually parse: withMax()
        // returns the raw DB string, and "2026-08-27 14:32:11" is read as
        // local time by some browsers and rejected by others.
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $row['pending_draft_at']
        );
    }

    public function test_listing_omits_the_draft_timestamp_when_there_is_no_draft(): void
    {
        $tour = Tour::factory()->create();

        $row = collect($this->getJson('/api/tours')->json('data'))
            ->firstWhere('id', $tour->id);

        $this->assertFalse($row['has_pending_draft']);
        // Absent rather than null: a date on a tour with no draft would make
        // the badge render "guardado hace…" for something that never existed.
        $this->assertArrayNotHasKey('pending_draft_at', $row);
    }

    public function test_listing_reports_which_languages_the_draft_changes(): void
    {
        $tour = Tour::factory()->create();
        Sanctum::actingAs($this->admin());

        $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'schema_version' => 'v1',
            'payload' => ['contentSEO' => ['en' => ['title' => 'New title']]],
            'changed_languages' => ['EN', 'ES'],
            'changed_sections' => ['Precios'],
        ])->assertOk();

        $row = collect($this->getJson('/api/tours')->json('data'))
            ->firstWhere('id', $tour->id);

        $this->assertSame(['EN', 'ES'], $row['pending_draft_languages']);
        $this->assertSame(['Precios'], $row['pending_draft_sections']);
    }

    public function test_a_draft_saved_without_a_summary_reports_unknown_not_empty(): void
    {
        $tour = Tour::factory()->create();
        Sanctum::actingAs($this->admin());

        // An older admin build, or any client that does not send the summary.
        $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'schema_version' => 'v1',
            'payload' => ['basicInfo' => ['title' => 'Parked']],
        ])->assertOk();

        $row = collect($this->getJson('/api/tours')->json('data'))
            ->firstWhere('id', $tour->id);

        // null, never []. An empty array would tell the operator "this draft
        // changes no language", which is a confident lie about parked work —
        // null lets the listing stay quiet instead.
        $this->assertTrue($row['has_pending_draft']);
        $this->assertNull($row['pending_draft_languages']);
        $this->assertNull($row['pending_draft_sections']);
    }

    public function test_the_listing_does_not_load_the_draft_payload(): void
    {
        $tour = Tour::factory()->create();
        Sanctum::actingAs($this->admin());

        $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'schema_version' => 'v1',
            // Payloads carry translated HTML for every language; pulling them
            // into the listing would move megabytes to render a badge.
            'payload' => ['basicInfo' => ['title' => str_repeat('x', 5000)]],
        ])->assertOk();

        $body = $this->getJson('/api/tours')->getContent();

        $this->assertStringNotContainsString(str_repeat('x', 5000), $body);
    }

    public function test_listing_names_the_primary_language_instead_of_guessing_from_the_code(): void
    {
        // A Portuguese tour whose business code starts with "BR". Reading the
        // language off the code produced "BR", which is not a language at all.
        $language = \App\Models\Language::firstOrCreate(
            ['code' => 'PT'],
            ['country' => 'Brasil', 'name' => 'Portugues', 'active' => true]
        );
        $tour = Tour::factory()->create([
            'code' => 'BR088',
            'primary_language_id' => $language->id,
        ]);

        $row = collect($this->getJson('/api/tours')->json('data'))
            ->firstWhere('id', $tour->id);

        $this->assertSame('PT', $row['primary_language']['code']);
    }

    public function test_an_older_draft_can_be_annotated_without_being_touched(): void
    {
        $tour = Tour::factory()->create();
        Sanctum::actingAs($this->admin());

        $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'schema_version' => 'v1',
            'payload' => ['contentSEO' => ['en' => ['title' => 'Parked work']]],
        ])->assertOk();

        $before = TourRevision::where('tour_id', $tour->id)->firstOrFail();
        // Age it, so a bumped timestamp would be unmistakable.
        $before->timestamps = false;
        $before->updated_at = now()->subDays(9);
        $before->save();
        $stamp = $before->fresh()->updated_at;

        $this->postJson("/api/admin/tours/{$tour->id}/revision/summary", [
            'changed_languages' => ['EN'],
            'changed_sections' => [],
        ])->assertOk();

        $after = TourRevision::where('tour_id', $tour->id)->firstOrFail();

        $this->assertSame(['EN'], $after->changed_languages);
        // The parked work itself is untouched...
        $this->assertSame(['contentSEO' => ['en' => ['title' => 'Parked work']]], $after->payload);
        // ...its version does not move, or another tab's next save would be
        // rejected as a conflict it never caused...
        $this->assertSame($before->version, $after->version);
        // ...and it does not look freshly edited: updated_at is what the list
        // renders as "hace 9 días", and annotating is not editing.
        $this->assertSame($stamp->toDateTimeString(), $after->updated_at->toDateTimeString());
    }

    public function test_annotating_requires_an_admin(): void
    {
        $tour = Tour::factory()->create();
        Sanctum::actingAs($this->customer());

        $this->postJson("/api/admin/tours/{$tour->id}/revision/summary", [
            'changed_languages' => ['EN'],
        ])->assertForbidden();
    }

    public function test_reading_a_draft_says_whether_it_carries_a_summary(): void
    {
        $tour = Tour::factory()->create();
        Sanctum::actingAs($this->admin());

        $this->postJson("/api/admin/tours/{$tour->id}/revision", [
            'schema_version' => 'v1',
            'payload' => ['basicInfo' => ['title' => 'Parked']],
        ])->assertOk();

        // null is the signal the wizard uses to decide it should fill this in.
        $this->getJson("/api/admin/tours/{$tour->id}/revision?schema_version=v1")
            ->assertOk()
            ->assertJsonPath('data.changed_languages', null);
    }

    // --- Public visibility ------------------------------------------------

    public function test_public_cannot_see_an_unpublished_tour(): void
    {
        $tour = Tour::factory()->draft()->withTranslation('ES', 'tour-oculto')->create();

        $this->getJson("/api/tours/{$tour->id}")->assertNotFound();
        $this->getJson('/api/tours/slug/tour-oculto')->assertNotFound();

        $listed = collect($this->getJson('/api/tours')->json('data'))->pluck('id');
        $this->assertNotContains($tour->id, $listed);
    }

    public function test_status_query_cannot_be_used_to_reach_drafts(): void
    {
        $tour = Tour::factory()->draft()->create();

        // The filter is a filter for admins and a hard rule for everyone else.
        $listed = collect($this->getJson('/api/tours?status=draft')->json('data'))->pluck('id');
        $this->assertNotContains($tour->id, $listed);
    }

    public function test_admins_can_still_see_unpublished_tours(): void
    {
        // The admin panel reads tours through these same public routes, so
        // hiding drafts from everyone would have broken the editor.
        $tour = Tour::factory()->draft()->create();
        Sanctum::actingAs($this->admin());

        $this->getJson("/api/tours/{$tour->id}")->assertOk();

        $listed = collect($this->getJson('/api/tours')->json('data'))->pluck('id');
        $this->assertContains($tour->id, $listed);
    }

    public function test_published_tours_stay_public(): void
    {
        $tour = Tour::factory()->withTranslation('ES', 'tour-visible')->create();

        $this->getJson("/api/tours/{$tour->id}")->assertOk();
        $this->getJson('/api/tours/slug/tour-visible')->assertOk();

        $listed = collect($this->getJson('/api/tours')->json('data'))->pluck('id');
        $this->assertContains($tour->id, $listed);
    }
}
