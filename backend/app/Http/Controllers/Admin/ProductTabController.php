<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tab;
use App\Models\AdditionalTab;
use Illuminate\Http\Request;

class ProductTabController extends Controller
{
    public function index(Product $product)
    {
        $product->load(['tab', 'additionalTabs']);
        return view('admin.products.tabs', compact('product'));
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'type' => 'required|in:main,additional',
            'title_es' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'content_es' => 'required|string',
            'content_en' => 'required|string',
            'order' => 'nullable|integer|min:0',
        ]);

        if ($validated['type'] === 'main') {
            // Delete existing main tab if exists
            if ($product->tab) {
                $product->tab->delete();
            }

            // Create main tab
            Tab::create([
                'product_id' => $product->id,
                'title' => json_encode([
                    'es' => $validated['title_es'],
                    'en' => $validated['title_en'],
                ]),
                'content' => json_encode([
                    'es' => $validated['content_es'],
                    'en' => $validated['content_en'],
                ]),
            ]);
        } else {
            // Create additional tab
            AdditionalTab::create([
                'product_id' => $product->id,
                'title' => json_encode([
                    'es' => $validated['title_es'],
                    'en' => $validated['title_en'],
                ]),
                'content' => json_encode([
                    'es' => $validated['content_es'],
                    'en' => $validated['content_en'],
                ]),
                'order' => $validated['order'] ?? 0,
            ]);
        }

        return redirect()->route('admin.products.tabs', $product)
            ->with('success', 'Tab agregado exitosamente');
    }

    public function destroy(Product $product, $tabId)
    {
        // Try to find in additional tabs
        $tab = AdditionalTab::find($tabId);

        if ($tab) {
            $tab->delete();
            return redirect()->route('admin.products.tabs', $product)
                ->with('success', 'Tab eliminado exitosamente');
        }

        // Try to find main tab
        $mainTab = Tab::find($tabId);
        if ($mainTab) {
            $mainTab->delete();
            return redirect()->route('admin.products.tabs', $product)
                ->with('success', 'Tab principal eliminado exitosamente');
        }

        return redirect()->route('admin.products.tabs', $product)
            ->with('error', 'Tab no encontrado');
    }
}
