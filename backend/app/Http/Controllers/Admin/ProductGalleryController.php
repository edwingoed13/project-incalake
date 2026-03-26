<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductGalleryController extends Controller
{
    public function index(Product $product)
    {
        $product->load('galleries');
        return view('admin.products.gallery', compact('product'));
    }

    public function store(Request $request, Product $product)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'title' => 'nullable|string|max:255',
            'order' => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('products/galleries', $filename, 'public');

            $gallery = Gallery::create([
                'image_path' => $path,
                'title' => $request->title,
                'order' => $request->order ?? 0,
            ]);

            $product->galleries()->attach($gallery->id);
        }

        return redirect()->route('admin.products.gallery', $product)
            ->with('success', 'Imagen agregada exitosamente');
    }

    public function destroy(Product $product, Gallery $gallery)
    {
        $product->galleries()->detach($gallery->id);

        if (Storage::disk('public')->exists($gallery->image_path)) {
            Storage::disk('public')->delete($gallery->image_path);
        }

        if ($gallery->products()->count() == 0) {
            $gallery->delete();
        }

        return redirect()->route('admin.products.gallery', $product)
            ->with('success', 'Imagen eliminada exitosamente');
    }
}
