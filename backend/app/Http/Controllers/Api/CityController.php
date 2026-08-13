<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    public function __construct(private CacheService $cacheService) {}

    /**
     * Display a listing of cities.
     *
     * The list is rendered in every public-site header (city dropdown) AND
     * in the /tours filter sidebar with optional ?with_tour_counts=1. Both
     * variants go through CacheService:
     *  - default (no counts) -> support cache, 24h TTL, busted by City save
     *  - with_tour_counts    -> versions with toursVersion so an active tour
     *                            toggle invalidates counts automatically
     *
     * Admin or non-public callers (active=0 or a search term) bypass the
     * cache so they always see fresh state — the public surface never asks
     * for those, so the cache hit rate stays high.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $isAdminQuery = $request->has('search')
                || ($request->has('active') && !$request->boolean('active'));

            // Public read with counts — the hottest path. Cache key versions
            // with the tours dataset so any tour toggle invalidates.
            if (!$isAdminQuery && $request->boolean('with_tour_counts')) {
                $lang = (string) $request->query('language', 'ES');
                $data = $this->cacheService->getCityCounts($lang, function () {
                    $cities = City::query()
                        ->where('active', true)
                        ->withCount(['tours as tours_count' => function ($q) {
                            $q->where('active', true)->where('status', 'published');
                        }])
                        ->orderBy('name')
                        ->limit(20)
                        ->get();
                    return $this->withCityImages($cities->toArray());
                });
                return response()->json(['success' => true, 'data' => $data]);
            }

            // Public read without counts — header dropdown, footer, etc.
            if (!$isAdminQuery) {
                $lang = (string) $request->query('language', 'ES');
                $data = $this->cacheService->getPublicCities($lang, function () {
                    return $this->withCityImages(
                        City::query()
                            ->where('active', true)
                            ->orderBy('name')
                            ->limit(20)
                            ->get()
                            ->toArray()
                    );
                });
                return response()->json(['success' => true, 'data' => $data]);
            }

            // Admin / filtered query — never cached.
            $query = City::query();
            if ($request->has('active')) {
                $query->where('active', $request->boolean('active'));
            }
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            if ($request->boolean('with_tour_counts')) {
                $query->withCount(['tours as tours_count' => function ($q) {
                    $q->where('active', true)->where('status', 'published');
                }]);
            }
            $cities = $query->orderBy('name')->limit(20)->get();

            return response()->json([
                'success' => true,
                'data' => $cities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ciudades.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Attach a representative photo to each city: the featured image of its
     * newest published tour.
     *
     * Cities have no image column, so the home page's destination grid used a
     * hardcoded map of Unsplash URLs — Puno and Copacabana shared one photo,
     * Cusco and Juliaca both showed Machu Picchu, and the fallback repeated
     * Puno's. This way the tile shows Incalake's own photography and follows
     * the catalog without anyone maintaining a second list. One extra query;
     * the result rides the same cache as the city list.
     */
    private function withCityImages(array $cities): array
    {
        $ids = array_values(array_filter(array_column($cities, 'id')));
        if (!$ids) {
            return $cities;
        }

        $images = \App\Models\Tour::query()
            ->whereIn('city_id', $ids)
            ->where('active', true)
            ->where('status', 'published')
            ->whereNotNull('featured_image')
            ->where('featured_image', '!=', '')
            // Ascending: pluck keys by city_id and each row overwrites the
            // previous, so the LAST one written — the newest tour — wins.
            ->orderBy('id')
            ->pluck('featured_image', 'city_id');

        foreach ($cities as &$city) {
            $city['image'] = $images[$city['id']] ?? null;
        }

        return $cities;
    }

    /**
     * Display the specified city.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $city = City::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $city
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ciudad no encontrada.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la ciudad.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin: city search proxied through Google Places REST (server key).
     * The Maps JS widget needed browser-side API enablement that kept
     * breaking; the REST autocomplete works with the existing server key and
     * keeps it out of the browser entirely.
     */
    public function placesSearch(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $apiKey = config('services.google_places.api_key');
        if (mb_strlen($q) < 2 || !$apiKey) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $res = \Illuminate\Support\Facades\Http::timeout(6)->get(
            'https://maps.googleapis.com/maps/api/place/autocomplete/json',
            ['input' => $q, 'types' => '(cities)', 'language' => 'es', 'key' => $apiKey]
        )->json();

        $out = [];
        foreach (($res['predictions'] ?? []) as $p) {
            $out[] = ['place_id' => $p['place_id'], 'description' => $p['description']];
        }

        return response()->json(['success' => true, 'data' => $out]);
    }

    /**
     * Admin: turn a Places pick (place_id) into a catalog city. Fetches the
     * details server-side (name, country, coordinates) and find-or-creates
     * via resolve() so the tour always ends up with a cities.id.
     */
    public function resolvePlace(Request $request): JsonResponse
    {
        $data = $request->validate(['place_id' => 'required|string|max:512']);
        $apiKey = config('services.google_places.api_key');
        if (!$apiKey) {
            return response()->json(['success' => false, 'message' => 'Places no configurado.'], 503);
        }

        $res = \Illuminate\Support\Facades\Http::timeout(6)->get(
            'https://maps.googleapis.com/maps/api/place/details/json',
            ['place_id' => $data['place_id'], 'fields' => 'name,geometry,address_components', 'language' => 'es', 'key' => $apiKey]
        )->json();

        $result = $res['result'] ?? null;
        if (!$result) {
            return response()->json(['success' => false, 'message' => 'Lugar no encontrado.'], 404);
        }

        $country = null;
        foreach (($result['address_components'] ?? []) as $c) {
            if (in_array('country', $c['types'] ?? [], true)) {
                $country = $c['short_name'] ?? null;
            }
        }

        $request->merge([
            'name' => $result['name'],
            'country_code' => $country,
            'latitude' => $result['geometry']['location']['lat'] ?? null,
            'longitude' => $result['geometry']['location']['lng'] ?? null,
        ]);

        return $this->resolve($request);
    }

    /**
     * Admin: resolve a Google Places pick to a catalog city — find it by
     * slug/name or create it. The tour editor must always end up with a
     * cities.id (public /[city]/[slug] URLs hang off it), so free-text
     * Places results can never stay as plain text. Cache invalidation is
     * handled by the City model observer (bumpSupportVersion).
     */
    public function resolve(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'country_code' => 'nullable|string|size:2',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $name = trim($data['name']);
        $slug = \Illuminate\Support\Str::slug($name);
        $country = strtoupper($data['country_code'] ?? 'PE');

        $city = City::withTrashed()
            ->where(function ($q) use ($slug, $name) {
                $q->where('slug', $slug)->orWhereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
            })
            ->first();

        if ($city) {
            if ($city->trashed()) {
                $city->restore();
            }
            $updates = [];
            if (!$city->active) {
                $updates['active'] = true;
            }
            if ($city->latitude === null && isset($data['latitude'])) {
                $updates['latitude'] = $data['latitude'];
                $updates['longitude'] = $data['longitude'] ?? null;
            }
            if ($updates) {
                $city->update($updates);
            }
        } else {
            $city = City::create([
                'name' => $name,
                'slug' => $slug,
                'country_code' => $country,
                // Peru and Bolivia are the operating area; both are DST-free.
                'timezone' => $country === 'BO' ? 'America/La_Paz' : 'America/Lima',
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'active' => true,
            ]);
        }

        return response()->json(['success' => true, 'data' => $city]);
    }
}
