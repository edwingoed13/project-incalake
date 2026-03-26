<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.languages.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.languages.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'country' => 'required|string|max:128',
            'code' => 'required|string|max:2',
        ]);

        Language::create([
            'country' => $validated['country'],
            'code' => strtoupper($validated['code']),
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.languages.index')
            ->with('success', 'Idioma creado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Language $language)
    {
        return view('admin.languages.show', compact('language'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Language $language)
    {
        return view('admin.languages.edit', compact('language'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Language $language)
    {
        $validated = $request->validate([
            'country' => 'required|string|max:128',
            'code' => 'required|string|max:2',
        ]);

        $language->update([
            'country' => $validated['country'],
            'code' => strtoupper($validated['code']),
        ]);

        return redirect()
            ->route('admin.languages.index')
            ->with('success', 'Idioma actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Language $language)
    {
        $language->delete();

        return redirect()
            ->route('admin.languages.index')
            ->with('success', 'Idioma eliminado exitosamente');
    }
}
