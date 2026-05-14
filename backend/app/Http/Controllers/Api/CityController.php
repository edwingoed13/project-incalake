<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    /**
     * Display a listing of cities.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = City::query();

            if ($request->has('active')) {
                $query->where('active', $request->boolean('active'));
            } else {
                $query->where('active', true);
            }

            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Frontend listing page asks for tour counts so it can show "Puno (12)" etc.
            // Doing this server-side via withCount is a single GROUP BY query — way
            // cheaper than the previous client-side approach of fetching all 500 tours
            // just to count them.
            if ($request->boolean('with_tour_counts')) {
                $query->withCount(['tours as tours_count' => function ($q) {
                    $q->where('active', true)->where('status', 'published');
                }]);
            }

            $cities = $query->orderBy('name')->limit(20)->get();

            return response()->json([
                'success' => true,
                'data' => $cities
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
