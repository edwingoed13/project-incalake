<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryCode;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Category::with('categoryCode')
            ->where('language_id', 1); // Spanish categories only

        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Group by category_code_id to avoid duplicates
        $categories = $query->get()
            ->unique('category_code_id')
            ->sortByDesc('created_at')
            ->values();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:64|unique:category_codes,code',
            'name_es' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
        ]);

        // Create category code
        $categoryCode = CategoryCode::create([
            'code' => $validated['code'],
        ]);

        // Create Spanish category
        Category::create([
            'name' => $validated['name_es'],
            'category_code_id' => $categoryCode->id,
            'language_id' => 1, // Spanish
        ]);

        // Create English category
        Category::create([
            'name' => $validated['name_en'],
            'category_code_id' => $categoryCode->id,
            'language_id' => 2, // English
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Categoría creada exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        $category->load(['categoryCode', 'products']);

        // Get English version
        $categoryEn = Category::where('category_code_id', $category->category_code_id)
            ->where('language_id', 2)
            ->first();

        return view('admin.categories.show', compact('category', 'categoryEn'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        $category->load('categoryCode');

        // Get English version
        $categoryEn = Category::where('category_code_id', $category->category_code_id)
            ->where('language_id', 2)
            ->first();

        return view('admin.categories.edit', compact('category', 'categoryEn'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name_es' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
        ]);

        // Update Spanish category
        $category->update([
            'name' => $validated['name_es'],
        ]);

        // Update English category
        $categoryEn = Category::where('category_code_id', $category->category_code_id)
            ->where('language_id', 2)
            ->first();

        if ($categoryEn) {
            $categoryEn->update([
                'name' => $validated['name_en'],
            ]);
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Categoría actualizada exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        // Delete both language versions
        Category::where('category_code_id', $category->category_code_id)->delete();

        // Delete category code
        $category->categoryCode()->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Categoría eliminada exitosamente');
    }
}
