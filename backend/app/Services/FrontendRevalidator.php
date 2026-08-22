<?php

namespace App\Services;

use App\Models\Tour;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Purges the public site's ISR cache for a tour right after it is published.
 *
 * Tour pages are cached by Vercel ISR (nuxt.config routeRules), so a publish
 * stayed invisible until the expiration elapsed — operators published, looked
 * at the site, saw the old copy and assumed the save had failed. A browser
 * hard refresh cannot help: the stale copy lives at Vercel's edge, not in the
 * browser.
 *
 * Vercel regenerates a path on demand when it receives a GET carrying
 * `x-prerender-revalidate: <bypassToken>`, which is what this fires.
 *
 * Best-effort by design: a publish must never fail because the public site was
 * slow to answer, so every error is logged and swallowed.
 */
class FrontendRevalidator
{
    /** Whole batch, not per request — a publish shouldn't hang on this. */
    private const TIMEOUT_SECONDS = 5;

    public function revalidateTour(Tour $tour): void
    {
        $token = config('services.frontend.revalidate_token');
        if (empty($token)) {
            // Not configured (e.g. local, or before the token is set on the
            // server). Silent: this is an optimisation, not a requirement.
            return;
        }

        $this->purge($this->urlsFor($tour), ['tour_id' => $tour->id]);
    }

    /**
     * Purge the surfaces that belong to no single tour: every locale's home and
     * tour listing.
     *
     * Needed because things OTHER than publishing a tour change what those
     * pages show — a review that moves a rating, home copy edited in the admin,
     * a city renamed. Publishing purges through revalidateTour(); these had
     * nothing, and with the ISR window now at an hour they would have sat
     * stale for up to that long instead of the old few minutes.
     */
    public function revalidateSharedSurfaces(): void
    {
        $token = config('services.frontend.revalidate_token');
        $base = rtrim((string) config('services.frontend.url'), '/');
        if (empty($token) || $base === '') {
            return;
        }

        $urls = [];
        foreach (['es', 'en', 'pt', 'fr', 'de', 'it'] as $locale) {
            $urls[] = "{$base}/{$locale}";
            $urls[] = "{$base}/{$locale}/tours";
        }

        $this->purge($urls, ['scope' => 'shared-surfaces']);
    }

    /**
     * Fire the revalidation GETs. Best-effort: a save must never fail because
     * the public site was slow to answer.
     *
     * @param string[] $urls
     */
    private function purge(array $urls, array $logContext = []): void
    {
        $token = config('services.frontend.revalidate_token');
        if (empty($token) || empty($urls)) {
            return;
        }

        try {
            $responses = Http::pool(fn ($pool) => array_map(
                fn ($url) => $pool->withHeaders(['x-prerender-revalidate' => $token])
                    ->timeout(self::TIMEOUT_SECONDS)
                    ->get($url),
                $urls
            ));

            $failed = [];
            $ignored = [];
            foreach ($responses as $i => $response) {
                $ok = is_object($response)
                    && method_exists($response, 'successful')
                    && $response->successful();
                if (!$ok) {
                    $failed[] = $urls[$i];
                    continue;
                }

                // A 200 is NOT proof the purge landed. Vercel answers normally
                // when it does not accept the token, and the old Apache site
                // answers 200 to anything — so for a long time every edit
                // failed to reach travellers while this logged nothing at all.
                // The header is the only honest signal: a cached response means
                // nothing was regenerated.
                $cache = strtoupper((string) $response->header('x-vercel-cache'));
                if ($cache === '' || $cache === 'HIT') {
                    $ignored[] = $urls[$i] . ' [x-vercel-cache: ' . ($cache ?: 'ausente') . ']';
                }
            }

            if ($failed) {
                Log::warning('ISR revalidation failed for some URLs', $logContext + ['failed' => $failed]);
            }

            if ($ignored) {
                Log::error(
                    'ISR revalidation was ACCEPTED BUT IGNORED - the public site is serving stale pages. '
                    . 'Check FRONTEND_PUBLIC_URL points at the Vercel deployment and that '
                    . 'FRONTEND_REVALIDATE_TOKEN matches VERCEL_BYPASS_TOKEN.',
                    $logContext + ['ignored' => $ignored]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('ISR revalidation threw', $logContext + ['error' => $e->getMessage()]);
        }
    }

    /**
     * Every public URL whose content this tour affects: its detail page in each
     * translated locale, plus that locale's tour listing (the card shows the
     * title, price and photo).
     *
     * @return string[]
     */
    private function urlsFor(Tour $tour): array
    {
        $base = rtrim((string) config('services.frontend.url'), '/');
        if ($base === '') {
            return [];
        }

        $tour->loadMissing(['translations.language', 'city']);

        $citySlug = $tour->city?->slug
            ?: Str::slug((string) ($tour->city_name ?? ''))
            ?: 'puno';

        $urls = [];
        $locales = [];

        foreach ($tour->translations as $translation) {
            $locale = strtolower((string) ($translation->language?->code ?? ''));
            $slug = trim((string) $translation->slug);
            if ($locale === '' || $slug === '') {
                continue;
            }
            $locales[$locale] = true;
            $urls[] = "{$base}/{$locale}/{$citySlug}/{$slug}";
        }

        foreach (array_keys($locales) as $locale) {
            $urls[] = "{$base}/{$locale}/tours";
            // The home shows recommended tours, destination photos and offer
            // cards — with its 15-minute ISR window it was the surface where
            // "publiqué y no se ve" kept happening after the listing got fast.
            $urls[] = "{$base}/{$locale}";
        }

        return array_values(array_unique($urls));
    }
}
