<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\TourRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Editorial triage on the tour list.
 *
 * The list could always SHOW that a tour was empty, had parked edits, or
 * carried a title copied from another tour — but it could not filter by any of
 * it. Finding those tours meant paging the whole catalogue, which is how 23
 * tours stayed published with no content at all. These tests pin the filter and,
 * just as importantly, that its counter counts the same rows it returns.
 */
class TourAttentionFilterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function ids(array $json): array
    {
        return collect($json['data'] ?? [])->pluck('id')->sort()->values()->all();
    }

    public function test_it_finds_tours_published_with_no_content(): void
    {
        $vacio = Tour::factory()->create(['status' => 'published']);
        $sano = Tour::factory()->withTranslation('ES', 'tour-sano')->create(['status' => 'published']);
        // A DRAFT with no content is a tour being built, not a problem.
        $borrador = Tour::factory()->draft()->create();
        Sanctum::actingAs($this->admin());

        $encontrados = $this->ids($this->getJson('/api/tours?attention=no_content')->assertOk()->json());

        $this->assertContains($vacio->id, $encontrados);
        $this->assertNotContains($sano->id, $encontrados);
        $this->assertNotContains($borrador->id, $encontrados);
    }

    public function test_it_finds_tours_with_parked_edits(): void
    {
        $conBorrador = Tour::factory()->withTranslation('ES', 'con-borrador')->create();
        $sinBorrador = Tour::factory()->withTranslation('ES', 'sin-borrador')->create();
        TourRevision::create([
            'tour_id' => $conBorrador->id,
            'payload' => ['basicInfo' => ['title' => 'parked']],
            'schema_version' => 'v1',
        ]);
        Sanctum::actingAs($this->admin());

        $encontrados = $this->ids($this->getJson('/api/tours?attention=pending_draft')->assertOk()->json());

        $this->assertSame([$conBorrador->id], $encontrados);
        $this->assertNotContains($sinBorrador->id, $encontrados);
    }

    public function test_it_finds_titles_copied_from_another_tour(): void
    {
        // Same language, byte-identical title, different tours: both sides of
        // the collision are flagged, because the data cannot say which is the
        // copy.
        $uno = Tour::factory()->withTranslation('ES', 'uros-a')->create();
        $dos = Tour::factory()->withTranslation('ES', 'uros-b')->create();
        $propio = Tour::factory()->withTranslation('ES', 'unico')->create();

        $titulo = $uno->translations()->first()->h1_title;
        $dos->translations()->first()->update(['h1_title' => $titulo]);
        $propio->translations()->first()->update(['h1_title' => 'Un título que no repite nadie']);

        Sanctum::actingAs($this->admin());
        $encontrados = $this->ids($this->getJson('/api/tours?attention=duplicate_titles')->assertOk()->json());

        $this->assertContains($uno->id, $encontrados);
        $this->assertContains($dos->id, $encontrados);
        $this->assertNotContains($propio->id, $encontrados);
    }

    public function test_any_is_the_union_and_does_not_repeat_a_tour(): void
    {
        $vacio = Tour::factory()->create(['status' => 'published']);
        $conBorrador = Tour::factory()->withTranslation('ES', 'con-borrador-2')->create();
        TourRevision::create([
            'tour_id' => $conBorrador->id,
            'payload' => ['basicInfo' => []],
            'schema_version' => 'v1',
        ]);
        $sano = Tour::factory()->withTranslation('ES', 'sano-2')->create(['status' => 'published']);
        $sano->translations()->first()->update(['h1_title' => 'Título irrepetible']);

        Sanctum::actingAs($this->admin());
        $encontrados = $this->ids($this->getJson('/api/tours?attention=any')->assertOk()->json());

        $this->assertContains($vacio->id, $encontrados);
        $this->assertContains($conBorrador->id, $encontrados);
        $this->assertNotContains($sano->id, $encontrados);
        // A tour in two categories at once must appear once, not twice.
        $this->assertSame(count($encontrados), count(array_unique($encontrados)));
    }

    public function test_the_counter_matches_the_rows_it_filters(): void
    {
        Tour::factory()->create(['status' => 'published']);
        Tour::factory()->create(['status' => 'published']);
        $sano = Tour::factory()->withTranslation('ES', 'sano-3')->create(['status' => 'published']);
        $sano->translations()->first()->update(['h1_title' => 'Otro título irrepetible']);

        Sanctum::actingAs($this->admin());

        $contador = $this->getJson('/api/tours')->assertOk()->json('status_counts.attention');
        $filas = count($this->ids($this->getJson('/api/tours?attention=any&per_page=100')->assertOk()->json()));

        // A chip that disagrees with the list it opens is worse than no chip.
        $this->assertSame($filas, $contador);
    }

    public function test_it_finds_tours_missing_a_language(): void
    {
        $soloEs = Tour::factory()->withTranslation('ES', 'solo-es')->create();
        $conIngles = Tour::factory()->withTranslation('ES', 'con-en')->withTranslation('EN', 'with-en')->create();
        Sanctum::actingAs($this->admin());

        $encontrados = $this->ids($this->getJson('/api/tours?missing_language=EN&per_page=100')->assertOk()->json());

        $this->assertContains($soloEs->id, $encontrados);
        $this->assertNotContains($conIngles->id, $encontrados);
    }

    public function test_the_public_cannot_use_it_to_list_broken_tours(): void
    {
        Tour::factory()->create(['status' => 'published']);

        // Anonymous callers already only see published tours; letting them
        // filter for the empty ones would hand out a list of broken pages.
        $anonimo = $this->getJson('/api/tours?attention=no_content')->assertOk()->json();
        $sinFiltro = $this->getJson('/api/tours')->assertOk()->json();

        $this->assertSame(count($sinFiltro['data']), count($anonimo['data']));
    }
}
