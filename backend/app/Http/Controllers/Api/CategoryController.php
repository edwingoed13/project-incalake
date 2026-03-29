<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories with filters and pagination
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Category::query();

            // Eager loading
            $query->with(['translations', 'tours']);

            // Filter by language_id
            if ($request->has('language_id')) {
                $query->where('language_id', $request->language_id);
            }

            // Search by name
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Count products
            if ($request->boolean('with_products_count')) {
                $query->withCount('products');
            }

            // Sort order
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $categories = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => CategoryResource::collection($categories),
                'meta' => [
                    'current_page' => $categories->currentPage(),
                    'from' => $categories->firstItem(),
                    'last_page' => $categories->lastPage(),
                    'per_page' => $categories->perPage(),
                    'to' => $categories->lastItem(),
                    'total' => $categories->total(),
                ],
                'links' => [
                    'first' => $categories->url(1),
                    'last' => $categories->url($categories->lastPage()),
                    'prev' => $categories->previousPageUrl(),
                    'next' => $categories->nextPageUrl(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las categorías.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created category
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Create category in categories_new table
            $category = Category::create([
                'code' => $request->slug ?? \Illuminate\Support\Str::slug($request->name),
                'active' => $request->active ?? true,
            ]);

            // Create translation for Spanish (language_id = 1)
            $category->translations()->create([
                'language_id' => 1,
                'name' => $request->name,
                'description' => $request->description,
                'slug' => $request->slug,
            ]);

            DB::commit();

            $category->load(['translations', 'tours']);

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada exitosamente.',
                'data' => $category,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la categoría.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified category
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $category = Category::with(['translations', 'tours'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $category,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la categoría.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified category
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $category = Category::findOrFail($id);

            // Update category in categories_new table
            $category->update([
                'code' => $request->slug ?? $category->code,
                'active' => $request->active ?? $category->active,
            ]);

            // Update translation for Spanish (language_id = 1)
            $translation = $category->translations()->where('language_id', 1)->first();
            if ($translation) {
                $translation->update([
                    'name' => $request->name,
                    'description' => $request->description,
                    'slug' => $request->slug,
                ]);
            } else {
                // Create translation if it doesn't exist
                $category->translations()->create([
                    'language_id' => 1,
                    'name' => $request->name,
                    'description' => $request->description,
                    'slug' => $request->slug,
                ]);
            }

            DB::commit();

            $category->load(['translations', 'tours']);

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada exitosamente.',
                'data' => $category,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada.',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la categoría.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified category (soft delete)
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada exitosamente.',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la categoría.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
