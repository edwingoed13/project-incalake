<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    /**
     * Display a listing of languages
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Language::query();

            // Eager loading
            $query->with(['user']);

            // Count relationships
            if ($request->boolean('with_counts')) {
                $query->withCount(['categories', 'services']);
            }

            // Filter by code
            if ($request->has('code')) {
                $query->where('code', $request->code);
            }

            // Search by country
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('country', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            // Sort order
            $sortBy = $request->get('sort_by', 'country');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Return all or paginated
            if ($request->boolean('all')) {
                $languages = $query->get();
                return response()->json([
                    'success' => true,
                    'data' => LanguageResource::collection($languages),
                ], 200);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $languages = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => LanguageResource::collection($languages),
                'meta' => [
                    'current_page' => $languages->currentPage(),
                    'from' => $languages->firstItem(),
                    'last_page' => $languages->lastPage(),
                    'per_page' => $languages->perPage(),
                    'to' => $languages->lastItem(),
                    'total' => $languages->total(),
                ],
                'links' => [
                    'first' => $languages->url(1),
                    'last' => $languages->url($languages->lastPage()),
                    'prev' => $languages->previousPageUrl(),
                    'next' => $languages->nextPageUrl(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los idiomas.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified language
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $language = Language::with([
                'user',
                'categories',
                'services'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new LanguageResource($language),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Idioma no encontrado.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el idioma.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
