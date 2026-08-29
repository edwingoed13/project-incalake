<?php

namespace Tests\Feature;

use App\Models\AgeStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Age bands are global: every tour's prices hang off the same rows, so these
 * endpoints move the whole catalogue at once. They exist because nothing could
 * write them before — the wizard carried a hardcoded copy and discarded what
 * the operator typed, which is how production came to have the band the
 * pricing treats as adult named "Niño 0-3".
 */
class AgeStageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function stage(array $attrs = []): AgeStage
    {
        return AgeStage::create(array_merge([
            'description' => 'Adulto',
            'min_age' => 16,
            'max_age' => 99,
            'editable' => true,
        ], $attrs));
    }

    public function test_an_admin_can_read_the_age_bands(): void
    {
        $this->stage(['description' => 'Adulto']);
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/admin/age-stages')
            ->assertOk()
            ->assertJsonPath('data.0.description', 'Adulto')
            ->assertJsonPath('data.0.min_age', 16);
    }

    public function test_the_bands_are_not_public(): void
    {
        $this->stage();

        // They shape what every tour page prints about who counts as a child;
        // writing them is admin-only, and so is enumerating them.
        $this->getJson('/api/admin/age-stages')->assertUnauthorized();
    }

    public function test_an_admin_can_correct_a_band(): void
    {
        $stage = $this->stage(['description' => 'Niño', 'min_age' => 0, 'max_age' => 3]);
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/admin/age-stages', [
            'stages' => [
                ['id' => $stage->id, 'description' => 'Adulto', 'min_age' => 16, 'max_age' => 99],
            ],
        ])->assertOk();

        $stage->refresh();
        $this->assertSame('Adulto', $stage->description);
        $this->assertSame(16, $stage->min_age);
        $this->assertSame(99, $stage->max_age);
    }

    public function test_a_band_that_ends_before_it_starts_is_rejected(): void
    {
        $stage = $this->stage(['min_age' => 16, 'max_age' => 99]);
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/admin/age-stages', [
            'stages' => [
                ['id' => $stage->id, 'description' => 'Adulto', 'min_age' => 40, 'max_age' => 10],
            ],
        ])->assertStatus(422);

        // Nothing is written: a screen that saves several bands at once must
        // not leave half of them applied.
        $stage->refresh();
        $this->assertSame(16, $stage->min_age);
    }

    public function test_one_bad_row_does_not_apply_the_good_ones(): void
    {
        $ok = $this->stage(['description' => 'Adulto', 'min_age' => 16, 'max_age' => 99]);
        $bad = $this->stage(['description' => 'Niño', 'min_age' => 3, 'max_age' => 11]);
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/admin/age-stages', [
            'stages' => [
                ['id' => $ok->id, 'description' => 'Adulto', 'min_age' => 18, 'max_age' => 99],
                ['id' => $bad->id, 'description' => 'Niño', 'min_age' => 30, 'max_age' => 5],
            ],
        ])->assertStatus(422);

        $this->assertSame(16, $ok->fresh()->min_age);
    }

    public function test_a_band_marked_not_editable_is_left_alone(): void
    {
        $locked = $this->stage(['description' => 'Adulto Mayor', 'min_age' => 66, 'max_age' => 99, 'editable' => false]);
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/admin/age-stages', [
            'stages' => [
                ['id' => $locked->id, 'description' => 'Cambiado', 'min_age' => 1, 'max_age' => 2],
            ],
        ])->assertOk();

        // The flag is enforced here, not left to the client to respect.
        $this->assertSame('Adulto Mayor', $locked->fresh()->description);
        $this->assertSame(66, $locked->fresh()->min_age);
    }

    public function test_a_customer_cannot_change_the_bands(): void
    {
        $stage = $this->stage();
        Sanctum::actingAs(User::factory()->create(['role' => 'customer']));

        $this->postJson('/api/admin/age-stages', [
            'stages' => [
                ['id' => $stage->id, 'description' => 'Gratis', 'min_age' => 0, 'max_age' => 120],
            ],
        ])->assertForbidden();

        $this->assertSame('Adulto', $stage->fresh()->description);
    }
}
