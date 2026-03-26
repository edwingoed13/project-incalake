<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of products with filters and pagination
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Product::query();

            // Eager loading to prevent N+1 queries
            $query->with([
                'service',
                'productCode',
                'categories',
                'galleries',
                'tab',
                'additionalTabs',
                'priceDetails.ageStage',
                'priceDetails.nationality',
                'priceDetails.prices',
            ]);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->boolean('status'));
            }

            // Filter by service_id
            if ($request->has('service_id')) {
                $query->where('service_id', $request->service_id);
            }

            // Filter by category
            if ($request->has('category_id')) {
                $query->whereHas('categories', function ($q) use ($request) {
                    $q->where('categories.id', $request->category_id);
                });
            }

            // Search by title or subtitle
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('subtitle', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            // Filter by nearest_city
            if ($request->has('nearest_city')) {
                $query->where('nearest_city', 'like', "%{$request->nearest_city}%");
            }

            // Sort order
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => ProductResource::collection($products),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'from' => $products->firstItem(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'to' => $products->lastItem(),
                    'total' => $products->total(),
                ],
                'links' => [
                    'first' => $products->url(1),
                    'last' => $products->url($products->lastPage()),
                    'prev' => $products->previousPageUrl(),
                    'next' => $products->nextPageUrl(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los productos.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created product
     *
     * @param StoreProductRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreProductRequest $request)
    {
        DB::beginTransaction();
        try {
            $product = Product::create($request->except('categories'));

            if ($request->has('categories')) {
                $product->categories()->attach($request->categories);
            }

            $product->load([
                'service',
                'productCode',
                'categories',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente.',
                'data' => new ProductResource($product),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el producto.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified product
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $product = Product::with([
                'service.language',
                'productCode',
                'categories.language',
                'galleries',
                'tab',
                'additionalTabs',
                'priceDetails.ageStage',
                'priceDetails.nationality',
                'priceDetails.prices',
                'availabilities',
                'blockouts',
                'offers',
                'coupons',
                'resources',
                'formFields',
                'serviceDetails',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new ProductResource($product),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el producto.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified product
     *
     * @param UpdateProductRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateProductRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $product = Product::findOrFail($id);
            $product->update($request->except('categories'));

            if ($request->has('categories')) {
                $product->categories()->sync($request->categories);
            }

            $product->load([
                'service',
                'productCode',
                'categories',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado exitosamente.',
                'data' => new ProductResource($product),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado.',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el producto.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified product (soft delete)
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado exitosamente.',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el producto.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

