<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.products.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = \App\Models\Category::where('language_id', 1) // Spanish
            ->with('categoryCode')
            ->get()
            ->unique('category_code_id');

        $services = \App\Models\Service::where('language_id', 1)
            ->get();

        return view('admin.products.create', compact('categories', 'services'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:64|unique:products,code',
            'title_es' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'subtitle_es' => 'nullable|string|max:255',
            'subtitle_en' => 'nullable|string|max:255',
            'service_id' => 'required|exists:services,id',
            'duration' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'start_time' => 'nullable|string',
            'nearest_city' => 'nullable|string|max:255',
            'nearest_airport' => 'nullable|string|max:255',
            'status' => 'boolean',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ]);

        // Create product code
        $productCode = \App\Models\ProductCode::create([
            'code' => $validated['code'],
            'user_id' => auth()->id(),
        ]);

        // Create product
        $product = Product::create([
            'title' => json_encode([
                'es' => $validated['title_es'],
                'en' => $validated['title_en'],
            ]),
            'subtitle' => json_encode([
                'es' => $validated['subtitle_es'] ?? '',
                'en' => $validated['subtitle_en'] ?? '',
            ]),
            'code' => $validated['code'],
            'nearest_city' => $validated['nearest_city'] ?? '',
            'nearest_airport' => $validated['nearest_airport'] ?? '',
            'service_id' => $validated['service_id'],
            'start_time' => $validated['start_time'] ?? '09:00:00',
            'duration' => $validated['duration'],
            'capacity' => $validated['capacity'],
            'product_code_id' => $productCode->id,
            'status' => $request->has('status') ? true : false,
            'data_requirement' => 1,
            'multiple_forms' => false,
        ]);

        // Attach categories
        if ($request->has('categories')) {
            $product->categories()->attach($validated['categories']);
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Producto creado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load(['categories', 'productCode', 'service', 'tab']);
        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $product->load(['categories', 'productCode', 'service']);

        $categories = \App\Models\Category::where('language_id', 1)
            ->with('categoryCode')
            ->get()
            ->unique('category_code_id');

        $services = \App\Models\Service::where('language_id', 1)
            ->get();

        $title = json_decode($product->title);
        $subtitle = json_decode($product->subtitle);

        return view('admin.products.edit', compact('product', 'categories', 'services', 'title', 'subtitle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'title_es' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'subtitle_es' => 'nullable|string|max:255',
            'subtitle_en' => 'nullable|string|max:255',
            'service_id' => 'required|exists:services,id',
            'duration' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'start_time' => 'nullable|string',
            'nearest_city' => 'nullable|string|max:255',
            'nearest_airport' => 'nullable|string|max:255',
            'status' => 'boolean',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $product->update([
            'title' => json_encode([
                'es' => $validated['title_es'],
                'en' => $validated['title_en'],
            ]),
            'subtitle' => json_encode([
                'es' => $validated['subtitle_es'] ?? '',
                'en' => $validated['subtitle_en'] ?? '',
            ]),
            'nearest_city' => $validated['nearest_city'] ?? '',
            'nearest_airport' => $validated['nearest_airport'] ?? '',
            'service_id' => $validated['service_id'],
            'start_time' => $validated['start_time'] ?? '09:00:00',
            'duration' => $validated['duration'],
            'capacity' => $validated['capacity'],
            'status' => $request->has('status') ? true : false,
        ]);

        // Sync categories
        if ($request->has('categories')) {
            $product->categories()->sync($validated['categories']);
        } else {
            $product->categories()->detach();
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Producto actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Producto eliminado exitosamente');
    }
}
