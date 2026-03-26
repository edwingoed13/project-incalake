<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\ProductGalleryController;
use App\Http\Controllers\Admin\ProductTabController;
use App\Http\Controllers\Admin\ProductPriceController;
use App\Http\Controllers\Admin\TourController;
use App\Http\Controllers\Admin\CouponController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Products Management
    Route::resource('products', ProductController::class);
    Route::get('products/{product}/gallery', [ProductGalleryController::class, 'index'])->name('products.gallery');
    Route::post('products/{product}/gallery', [ProductGalleryController::class, 'store'])->name('products.gallery.store');
    Route::delete('products/{product}/gallery/{gallery}', [ProductGalleryController::class, 'destroy'])->name('products.gallery.destroy');
    Route::get('products/{product}/tabs', [ProductTabController::class, 'index'])->name('products.tabs');
    Route::post('products/{product}/tabs', [ProductTabController::class, 'store'])->name('products.tabs.store');
    Route::delete('products/{product}/tabs/{tab}', [ProductTabController::class, 'destroy'])->name('products.tabs.destroy');
    Route::get('products/{product}/prices', [ProductPriceController::class, 'index'])->name('products.prices');
    Route::post('products/{product}/prices', [ProductPriceController::class, 'store'])->name('products.prices.store');
    Route::delete('products/{product}/prices/{priceDetail}', [ProductPriceController::class, 'destroy'])->name('products.prices.destroy');

    // Categories Management
    Route::resource('categories', CategoryController::class);

    // Bookings Management
    Route::resource('bookings', BookingController::class);


    // Tours Management
    Route::resource('tours', TourController::class);
    
    // Coupons Management
    Route::resource('coupons', CouponController::class);

    // Test Tiptap Editor
    Route::get('/test-tiptap', function () {
        return view('admin.test-tiptap');
    })->name('test-tiptap');

    // Languages Management
    Route::resource('languages', LanguageController::class);
});

require __DIR__.'/auth.php';
