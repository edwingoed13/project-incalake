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
                    return $cities->toArray();
                });
                return response()->json(['success' => true, 'data' => $data]);
            }

            // Public read without counts — header dropdown, footer, etc.
            if (!$isAdminQuery) {
                $lang = (string) $request->query('language', 'ES');
                $data = $this->cacheService->getPublicCities($lang, function () {
                    return City::query()
                        ->where('active', true)
                        ->orderBy('name')
                        ->limit(20)
                        ->get()
                        ->toArray();
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
}
