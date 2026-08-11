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

        $urls = $this->urlsFor($tour);
        if (empty($urls)) {
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
            foreach ($responses as $i => $response) {
                $ok = is_object($response)
                    && method_exists($response, 'successful')
                    && $response->successful();
                if (!$ok) {
                    $failed[] = $urls[$i];
                }
            }

            if ($failed) {
                Log::warning('ISR revalidation failed for some tour URLs', [
                    'tour_id' => $tour->id,
                    'failed' => $failed,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('ISR revalidation threw', [
                'tour_id' => $tour->id,
                'error' => $e->getMessage(),
            ]);
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
        }

        return array_values(array_unique($urls));
    }
}
