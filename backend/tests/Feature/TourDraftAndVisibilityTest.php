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

    public function test_a_draft_from_an_older_editor_version_is_discarded(): void
    {
        $tour = Tour::factory()->create();
        TourRevision::create([
            'tour_id' => $tour->id,
            'payload' => ['basicInfo' => ['title' => 'forma antigua']],
            'schema_version' => 'v0',
        ]);
        Sanctum::actingAs($this->admin());

        // Restoring a v0 payload into a v1 wizard would put values in fields
        // that no longer mean the same thing, so it's dropped instead.
        $this->getJson("/api/admin/tours/{$tour->id}/revision?schema_version=v1")
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('tour_revisions', ['tour_id' => $tour->id]);
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
