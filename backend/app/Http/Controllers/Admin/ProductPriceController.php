<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PriceDetail;
use App\Models\AgeStage;
use Illuminate\Http\Request;

class ProductPriceController extends Controller
{
    public function index(Product $product)
    {
        $product->load('priceDetails.ageStage');
        $ageStages = AgeStage::all();

        return view('admin.products.prices', compact('product', 'ageStages'));
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'age_stage_id' => 'nullable|exists:age_stages,id',
            'age_range' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'description' => 'nullable|string|max:255',
        ]);

        PriceDetail::create([
            'product_id' => $product->id,
            'age_stage_id' => $validated['age_stage_id'],
            'age_range' => $validated['age_range'],
            'price' => $validated['price'],
            'currency' => $validated['currency'],
            'description' => $validated['description'],
        ]);

        return redirect()->route('admin.products.prices', $product)
            ->with('success', 'Precio agregado exitosamente');
    }

    public function destroy(Product $product, PriceDetail $priceDetail)
    {
        $priceDetail->delete();

        return redirect()->route('admin.products.prices', $product)
            ->with('success', 'Precio eliminado exitosamente');
    }
}
