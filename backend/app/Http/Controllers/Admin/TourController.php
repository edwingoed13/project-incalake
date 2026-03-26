<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use Illuminate\Http\Request;

class TourController extends Controller
{
    public function index()
    {
        return view('admin.tours.index');
    }

    public function create()
    {
        return view('admin.tours.create');
    }

    public function store(Request $request)
    {
        return redirect()->route('admin.tours.index')
            ->with('success', 'Tour creado exitosamente');
    }

    public function show(Tour $tour)
    {
        $tour->load(['translations.language', 'city', 'prices', 'categories']);
        return view('admin.tours.show', compact('tour'));
    }

    public function edit(Tour $tour)
    {
        $tour->load(['translations.language', 'prices', 'categories']);
        return view('admin.tours.edit', compact('tour'));
    }

    public function update(Request $request, Tour $tour)
    {
        return redirect()->route('admin.tours.index')
            ->with('success', 'Tour actualizado exitosamente');
    }

    public function destroy(Tour $tour)
    {
        $tour->delete();
        return redirect()->route('admin.tours.index')
            ->with('success', 'Tour eliminado exitosamente');
    }
}
