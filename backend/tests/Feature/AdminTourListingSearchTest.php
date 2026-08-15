<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The admin list always sends `search`, empty when nobody typed. That empty
 * string used to run a LIKE '%%' over translations, which silently required a
 * translation row — 23 production tours with no translations were invisible on
 * the only screen that could fix or delete them, while the tab counters (built
 * from a different query) kept showing the real total. Hence "267 tours" in the
 * header next to "Todos 290" in the tabs.
 */
class AdminTourListingSearchTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        Sanctum::actingAs($admin);
    }

    public function test_an_empty_search_lists_tours_that_have_no_translations(): void
    {
        $this->actingAsAdmin();
        $withTranslation = Tour::factory()->withTranslation('ES')->create();
        $orphan = Tour::factory()->create();   // shell tour, zero translations

        $data = $this->getJson('/api/tours?per_page=50&search=')->assertOk()->json();
        $ids = array_column($data['data'], 'id');

        $this->assertContains($orphan->id, $ids, 'a translation-less tour must still be manageable');
        $this->assertContains($withTranslation->id, $ids);
        // The header total and the tab counter must agree.
        $this->assertSame($data['status_counts']['all'], $data['meta']['total']);
    }

    public function test_the_code_is_searchable_like_the_placeholder_promises(): void
    {
        $this->actingAsAdmin();
        $target = Tour::factory()->create(['code' => 'ES999']);
        Tour::factory()->create(['code' => 'XX111']);

        $data = $this->getJson('/api/tours?per_page=50&search=ES999')->assertOk()->json();

        $this->assertSame([$target->id], array_column($data['data'], 'id'));
        $this->assertSame(1, $data['status_counts']['all'], 'tab counters follow the same filter');
    }

    public function test_a_real_search_still_narrows_by_title(): void
    {
        $this->actingAsAdmin();
        $match = Tour::factory()->withTranslation('ES')->create(['code' => 'AAA1']);
        $match->translations()->update(['h1_title' => 'Tour a las islas flotantes']);
        Tour::factory()->withTranslation('ES')->create(['code' => 'BBB2']);

        $ids = array_column(
            $this->getJson('/api/tours?per_page=50&search=flotantes')->assertOk()->json('data'),
            'id'
        );

        $this->assertSame([$match->id], $ids);
    }
}
