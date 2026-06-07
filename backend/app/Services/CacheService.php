<?php

namespace App\Services;

use App\Models\Tour;
use App\Models\CategoryNew;
use App\Models\Language;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    protected TourService $tourService;

    public function __construct(TourService $tourService)
    {
        $this->tourService = $tourService;
    }

    /**
     * Get tour details from cache (caches for 24 hours)
     */
    public function getTourDetails(int $tourId, ?string $languageCode = null): ?Tour
    {
        $cacheKey = $this->getTourCacheKey($tourId, $languageCode);

        try {
            return Cache::remember($cacheKey, config('constants.cache.tour.default_ttl'), function () use ($tourId, $languageCode, $cacheKey) {
                $query = Tour::with([
                    'translations',
                    'prices',
                    'mediaGallery',
                    'mapPoints',
                    'categories.translations',
                    'city',
                    'primaryLanguage'
                ]);

                if ($languageCode) {
                    $query->whereHas('translations', function ($q) use ($languageCode) {
                        $q->where('language_code', $languageCode);
                    });
                }

                $tour = $query->find($tourId);

                if (!$tour) {
                    Log::warning("Tour not found for caching", ['tour_id' => $tourId]);
                    return null;
                }

                Log::info("Tour details cached", ['tour_id' => $tourId, 'cache_key' => $cacheKey]);
                return $tour;
            });
        } catch (\Exception $e) {
            Log::error("Error caching tour details", [
                'tour_id' => $tourId,
                'error' => $e->getMessage()
            ]);
            return Tour::with(['translations', 'prices', 'mediaGallery', 'mapPoints', 'categories'])->find($tourId);
        }
    }

    /**
     * Cache tour list (caches for 1 hour)
     */
    public function getTourList(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = $this->getTourListCacheKey($filters);

        try {
            return Cache::remember($cacheKey, 3600, function () use ($filters) {
                $query = Tour::with(['city', 'primaryLanguage', 'mediaGallery'])
                    ->where('active', true);

                if (!empty($filters['status'])) {
                    $query->where('status', $filters['status']);
                }

                if (!empty($filters['service_type'])) {
                    $query->where('service_type', $filters['service_type']);
                }

                if (!empty($filters['difficulty'])) {
                    $query->where('difficulty', $filters['difficulty']);
                }

                if (!empty($filters['city_id'])) {
                    $query->where('city_id', $filters['city_id']);
                }

                $tours = $query->orderBy('created_at', 'desc')->get();

                Log::info("Tour list cached", ['filters' => $filters, 'count' => $tours->count()]);
                return $tours;
            });
        } catch (\Exception $e) {
            Log::error("Error caching tour list", [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            return Tour::with(['city', 'primaryLanguage'])->where('active', true)->get();
        }
    }

    /**
     * Public tour-listing cache (the /api/tours light grid).
     *
     * The expensive part is the query (178 tours + joins + min_price), not the
     * payload. We cache the already-built response array keyed by a VERSION +
     * the request params. The `database` cache driver has no tags/wildcards, so
     * invalidation is done by bumping the version (bumpToursVersion) on any
     * card-visible change — see the model observers in AppServiceProvider. That
     * makes edits show up immediately; the TTL is only a backstop.
     */
    // Bump when the listing PAYLOAD SHAPE changes in code (e.g. TourCardResource
    // fields). The data-driven version (bumpToursVersion) only covers DATA edits,
    // not code deploys — without this, a deploy keeps serving the old shape from
    // cache until the TTL. v2: dropped availability_data for a small `offer` field.
    // v4: offer exposes discount/dates/is_active/is_upcoming/discounted_min_price.
    // v5: card includes slim `places` (map_points filtered to sightseeing types)
    //     so the listing's Lugares filter + search ranking work without a refetch.
    // v6: listing hides child variants (parent_tour_id IS NOT NULL) so option
    //     groups collapse to one card on /tours.
    private const LISTING_CODE_VERSION = 6;

    // 24h backstop TTL. Real freshness comes from bumpToursVersion (fires on every
    // tour/translation/price/media save), so a long TTL just avoids cold rebuilds
    // (the slow ~1.5s query) without staleness — edits still invalidate instantly.
    private const PUBLIC_TTL = 86400;

    public function getPublicTourListing(array $params, \Closure $builder): array
    {
        $key = 'tours:public:c' . self::LISTING_CODE_VERSION
            . ':v' . self::toursVersion() . ':' . md5(serialize($params));

        try {
            return Cache::remember($key, self::PUBLIC_TTL, $builder);
        } catch (\Exception $e) {
            Log::error('Error caching public tour listing', ['error' => $e->getMessage()]);
            return $builder();
        }
    }

    /**
     * Public tour DETAIL cache (the /api/tours/{lang}/{city}/{slug} page).
     * Same versioned-key scheme as the listing — the model observers bump the
     * version on any tour/translation/price/media change, so edits show
     * immediately. The builder's ModelNotFoundException (404) propagates and is
     * NOT cached (Cache::remember only stores successful returns).
     */
    public function getPublicTourDetail(string $lang, string $citySlug, string $tourSlug, \Closure $builder): array
    {
        $key = 'tours:detail:c' . self::LISTING_CODE_VERSION . ':v' . self::toursVersion()
            . ':' . md5($lang . '|' . $citySlug . '|' . $tourSlug);

        try {
            return Cache::remember($key, self::PUBLIC_TTL, $builder);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e; // 404 must propagate, not be cached or swallowed
        } catch (\Exception $e) {
            Log::error('Error caching tour detail', ['error' => $e->getMessage()]);
            return $builder();
        }
    }

    // Static so model observers can bump the version without resolving the whole
    // service (which would pull in TourService + its sub-services on every save).
    public static function toursVersion(): int
    {
        return (int) Cache::get('tours:version', 1);
    }

    /**
     * Invalidate every cached public listing/detail at once by moving the
     * version forward. Old keys orphan and expire by TTL. Called from model
     * observers on every save/delete — we deliberately bump every time (no
     * process-lifetime static guard, which would skip later changes inside a
     * long-lived queue worker); a few extra cache writes per bulk save is cheap.
     */
    public static function bumpToursVersion(): void
    {
        try {
            Cache::forever('tours:version', self::toursVersion() + 1);
        } catch (\Exception $e) {
            Log::error('Error bumping tours cache version', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get categories from cache (caches for 48 hours)
     */
    public function getCategories(?string $languageCode = null): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "categories:" . ($languageCode ?? 'all');

        return Cache::remember($cacheKey, config('constants.cache.categories.default_ttl'), function () use ($languageCode) {
            $query = CategoryNew::where('active', true);

            if ($languageCode) {
                $query->with(['translations' => function ($q) use ($languageCode) {
                    $q->whereHas('language', function ($query) use ($languageCode) {
                        $query->where('code', $languageCode);
                    });
                }]);
            } else {
                $query->with('translations');
            }

            $categories = $query->orderBy('order')->get();

            Log::info("Categories cached", ['language' => $languageCode ?? 'all', 'count' => $categories->count()]);
            return $categories;
        });
    }

    /**
     * Get languages from cache (caches for 7 days)
     */
    public function getLanguages(): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = 'languages:all';

        return Cache::remember($cacheKey, 604800, function () {
            $languages = Language::orderBy('name')->get();

            Log::info("Languages cached", ['count' => $languages->count()]);
            return $languages;
        });
    }

    /**
     * Clear tour cache
     */
    public function clearTourCache(int $tourId): void
    {
        try {
            $patterns = [
                "tour:{$tourId}:*",
                "tour:{$tourId}",
            ];

            foreach ($patterns as $pattern) {
                Cache::forget($pattern);
            }

            Cache::forget('tour:list:*');
            Cache::tags(['tours'])->flush();

            Log::info("Tour cache cleared", ['tour_id' => $tourId]);
        } catch (\Exception $e) {
            Log::error("Error clearing tour cache", [
                'tour_id' => $tourId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clear categories cache
     */
    public function clearCategoriesCache(): void
    {
        try {
            Cache::forget('categories:*');
            Cache::tags(['categories'])->flush();

            Log::info("Categories cache cleared");
        } catch (\Exception $e) {
            Log::error("Error clearing categories cache", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Clear languages cache
     */
    public function clearLanguagesCache(): void
    {
        try {
            Cache::forget('languages:*');
            Cache::tags(['languages'])->flush();

            Log::info("Languages cache cleared");
        } catch (\Exception $e) {
            Log::error("Error clearing languages cache", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Clear all application cache
     */
    public function clearAllCache(): void
    {
        try {
            Cache::flush();
            Log::info("All cache cleared");
        } catch (\Exception $e) {
            Log::error("Error clearing all cache", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Generate tour cache key
     */
    protected function getTourCacheKey(int $tourId, ?string $languageCode): string
    {
        $prefix = config('constants.cache.tour.tags_prefix', 'tour');
        $language = $languageCode ?? 'default';
        return "{$prefix}:{$tourId}:{$language}";
    }

    /**
     * Generate tour list cache key
     */
    protected function getTourListCacheKey(array $filters): string
    {
        $signature = md5(serialize($filters));
        return "tour:list:{$signature}";
    }
}