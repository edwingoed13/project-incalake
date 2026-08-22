<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\TourRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reading a draft must not destroy it.
 *
 * The stale-schema branch used to delete the row before answering, so any
 * caller that got ?schema_version wrong — a typo, an older tab, a script —
 * silently wiped an operator's parked work and got a 200 back for it.
 */
class TourRevisionReadTest extends TestCase
{
    use RefreshDatabase;

    private function revision(Tour $tour, string $schema = 'v1'): TourRevision
    {
        return TourRevision::create([
            'tour_id' => $tour->id,
            'payload' => ['contentSEO' => ['es' => ['metaTitle' => 'Uyuni en 3 días']]],
            'schema_version' => $schema,
            'version' => 1,
            'updated_by' => User::factory()->create()->id,
        ]);
    }

    public function test_a_mismatched_schema_version_leaves_the_draft_alone(): void
    {
        $user = User::factory()->create();
        $tour = Tour::factory()->create();
        $this->revision($tour);

        $response = $this->actingAs($user)
            ->getJson("/api/admin/tours/{$tour->id}/revision?schema_version=1");

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => null, 'stale' => true]);

        $this->assertDatabaseCount('tour_revisions', 1);
    }

    public function test_a_matching_schema_version_returns_the_payload(): void
    {
        $user = User::factory()->create();
        $tour = Tour::factory()->create();
        $this->revision($tour);

        $this->actingAs($user)
            ->getJson("/api/admin/tours/{$tour->id}/revision?schema_version=v1")
            ->assertOk()
            ->assertJsonPath('data.payload.contentSEO.es.metaTitle', 'Uyuni en 3 días');

        $this->assertDatabaseCount('tour_revisions', 1);
    }
}
