<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Language;
use App\Models\PageContent;
use App\Models\Review;
use App\Models\Tour;
use App\Services\FrontendRevalidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Publishing a tour purges its page, the listing and the homes. But those
 * pages also change for reasons that are NOT a publish — a review moving a
 * rating on the cards, home copy edited in the admin, a city renamed — and
 * those had no purge at all. That was survivable while the ISR window was 5
 * minutes; it is an hour now, so it is not.
 */
class SharedSurfaceRevalidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.frontend.url' => 'https://site.test',
            'services.frontend.revalidate_token' => 'test-token',
        ]);
        Http::fake(['*' => Http::response('', 200)]);
    }

    private function purgedPaths(): array
    {
        $paths = [];
        foreach (Http::recorded() as [$request]) {
            // Only count requests carrying the revalidation header.
            if ($request->hasHeader('x-prerender-revalidate')) {
                $paths[] = parse_url($request->url(), PHP_URL_PATH);
            }
        }

        return $paths;
    }

    public function test_publishing_a_review_purges_the_homes_and_listings(): void
    {
        $tour = Tour::factory()->create();

        Review::create([
            'tour_id' => $tour->id, 'name' => 'Ana', 'rating' => 5,
            'comment' => 'Excelente', 'published' => true,
        ]);

        $paths = $this->purgedPaths();
        $this->assertContains('/es', $paths, 'the Spanish home shows ratings on its cards');
        $this->assertContains('/es/tours', $paths);
        $this->assertContains('/en/tours', $paths, 'every locale, not just Spanish');
    }

    public function test_editing_home_content_purges_the_homes(): void
    {
        // The column is a real FK, so the language has to exist.
        $language = Language::factory()->create(['code' => 'ES']);

        PageContent::create([
            'page' => 'home',
            'language_id' => $language->id,
            'content' => ['trust_signals' => []],
            'published' => true,
        ]);

        $this->assertContains('/es', $this->purgedPaths());
    }

    public function test_renaming_a_city_purges_the_shared_surfaces(): void
    {
        $city = City::create([
            'name' => 'Puno', 'slug' => 'puno', 'country_code' => 'PE',
            'timezone' => 'America/Lima', 'active' => true,
        ]);
        Http::fake(['*' => Http::response('', 200)]);   // reset recorded calls

        $city->update(['name' => 'Puno Centro']);

        $this->assertContains('/es', $this->purgedPaths());
    }

    /** A purge failure must never break the save that triggered it. */
    public function test_a_failing_purge_does_not_break_the_save(): void
    {
        Http::fake(['*' => Http::response('boom', 500)]);
        $tour = Tour::factory()->create();

        Review::create([
            'tour_id' => $tour->id, 'name' => 'Ana', 'rating' => 4,
            'comment' => 'Bien', 'published' => true,
        ]);

        $this->assertDatabaseHas('reviews', ['tour_id' => $tour->id, 'name' => 'Ana']);
    }

    /**
     * A 200 is not proof the purge landed, and this is the rule that decides.
     * Vercel returns HIT when it declines the token; a host that is not the
     * Vercel deployment sends no such header at all while still answering 200.
     *
     * @dataProvider vercelCacheHeaders
     */
    public function test_only_a_regenerated_response_counts_as_a_landed_purge(?string $header, bool $expected): void
    {
        $this->assertSame($expected, FrontendRevalidator::purgeLanded($header));
    }

    public static function vercelCacheHeaders(): array
    {
        return [
            'regenerada'              => ['MISS', true],
            'revalidada'              => ['REVALIDATED', true],
            'prerender bypass'        => ['BYPASS', true],
            'servida de cache (token rechazado)' => ['HIT', false],
            'minusculas'              => ['hit', false],
            'sin cabecera (no es Vercel)'        => [null, false],
            'cabecera vacia'          => ['', false],
            'solo espacios'           => ['   ', false],
        ];
    }
}
