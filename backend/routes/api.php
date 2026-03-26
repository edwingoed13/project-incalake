<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\TourController;
use App\Http\Controllers\Api\TourCloneController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\AITranslationSettingsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes - Authentication
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
});

// Public routes - Products (read-only)
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('api.products.index');
    Route::get('/{id}', [ProductController::class, 'show'])->name('api.products.show');
});

// Public routes - Categories (read-only)
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('api.categories.index');
    Route::get('/{id}', [CategoryController::class, 'show'])->name('api.categories.show');
});

// Public routes - Languages (read-only)
Route::prefix('languages')->group(function () {
    Route::get('/', [LanguageController::class, 'index'])->name('api.languages.index');
    Route::get('/{id}', [LanguageController::class, 'show'])->name('api.languages.show');
});

// City routes
Route::prefix('cities')->group(function () {
    Route::get('/', [CityController::class, 'index'])->name('api.cities.index');
    Route::get('/{id}', [CityController::class, 'show'])->name('api.cities.show');
});

// Public utility routes for tour creation form
Route::get('/admin/tours/generate-code', [TourController::class, 'generateCodeApi'])->name('api.admin.tours.generate-code');

// Public routes - Tours (read-only) - Rate Limited
Route::middleware(['throttle:60,1'])->prefix('tours')->group(function () {
    Route::get('/', [TourController::class, 'index'])->name('api.tours.index');
    Route::get('/slug/{slug}', [TourController::class, 'showBySlug'])->name('api.tours.show-by-slug');

    // NEW: Multilang route - /{lang}/{city}/{slug}
    Route::get('/{lang}/{citySlug}/{tourSlug}', [TourController::class, 'showMultilang'])->name('api.tours.multilang');

    Route::get('/{id}', [TourController::class, 'show'])->name('api.tours.show');
    Route::post('/{id}/calculate-price', [TourController::class, 'calculatePrice'])->name('api.tours.calculate-price');
    Route::post('/validate-coupon', [TourController::class, 'validateCoupon'])->name('api.tours.validate-coupon');
    Route::get('/{id}/available-dates', [TourController::class, 'getAvailableDates'])->name('api.tours.available-dates');

    // Clone/Translation endpoints
    Route::post('/{id}/clone', [TourCloneController::class, 'cloneManual'])->name('api.tours.clone');
    Route::post('/{id}/clone-ai', [TourCloneController::class, 'cloneWithAI'])->name('api.tours.clone-ai');
});

// Public routes - Coupons validation - Rate Limited
Route::middleware(['throttle:60,1'])->prefix('coupons')->group(function () {
    Route::post('/validate', [CouponController::class, 'validate'])->name('api.coupons.validate');
});

// Public routes - Bookings (checkout) - Rate Limited
Route::middleware(['throttle:60,1'])->prefix('bookings')->group(function () {
    Route::post('/', [BookingController::class, 'create'])->name('api.bookings.create');
    Route::get('/token/{token}', [BookingController::class, 'showByToken'])->name('api.bookings.show.token');
    Route::get('/{bookingCode}', [BookingController::class, 'show'])->name('api.bookings.show');
    Route::post('/{id}/payment/culqi', [BookingController::class, 'confirmCulqiPayment'])->name('api.bookings.payment.culqi');
    Route::post('/{id}/payment/paypal', [BookingController::class, 'confirmPayPalPayment'])->name('api.bookings.payment.paypal');
    Route::post('/{id}/cancel', [BookingController::class, 'cancel'])->name('api.bookings.cancel');
});

// Public routes - Payment configuration
Route::get('/payment/config', [BookingController::class, 'paymentConfig'])->name('api.payment.config');

// AI Translation Settings routes
Route::prefix('ai-translation-settings')->group(function () {
    Route::get('/', [AITranslationSettingsController::class, 'index'])->name('api.ai-translation-settings.index');
    Route::post('/', [AITranslationSettingsController::class, 'store'])->name('api.ai-translation-settings.store');
});

Route::post('/ai-translation-test', [AITranslationSettingsController::class, 'test'])->name('api.ai-translation-test');

// Protected routes - Require authentication with Sanctum
Route::middleware('auth:sanctum')->group(function () {

    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('api.auth.me');
    });

    // Bookings - List user's bookings (authenticated only)
    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index'])->name('api.bookings.index');
    });

    // Admin routes - Products management
    Route::prefix('admin/products')->group(function () {
        Route::post('/', [ProductController::class, 'store'])->name('api.admin.products.store');
        Route::put('/{id}', [ProductController::class, 'update'])->name('api.admin.products.update');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->name('api.admin.products.destroy');
    });

    // Admin routes - Categories management
    Route::prefix('admin/categories')->group(function () {
        Route::post('/', [CategoryController::class, 'store'])->name('api.admin.categories.store');
        Route::put('/{id}', [CategoryController::class, 'update'])->name('api.admin.categories.update');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('api.admin.categories.destroy');
    });

    // Admin routes - Tours management - More restrictive rate limit
    Route::middleware(['throttle:30,1'])->prefix('admin/tours')->group(function () {
        Route::post('/', [TourController::class, 'store'])->name('api.admin.tours.store');
        Route::put('/{id}', [TourController::class, 'update'])->name('api.admin.tours.update');
        Route::delete('/{id}', [TourController::class, 'destroy'])->name('api.admin.tours.destroy');
        Route::post('/upload-image', [UploadController::class, 'uploadTourImage'])->name('api.admin.tours.upload-image');
    });
});

// Booking confirmation routes (post-payment)
use App\Http\Controllers\Api\BookingConfirmationController;

Route::prefix('bookings')->group(function () {
    Route::get('/{id}/pickup-details', [BookingConfirmationController::class, 'getPickupDetails'])
        ->name('api.bookings.pickup-details');
    Route::post('/{id}/validate-hotel', [BookingConfirmationController::class, 'validateHotelLocation'])
        ->name('api.bookings.validate-hotel');
    Route::post('/{id}/save-pickup', [BookingConfirmationController::class, 'savePickupDetails'])
        ->name('api.bookings.save-pickup');
});

// Fallback route for undefined API routes
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Ruta no encontrada. Verifique la URL de la API.',
    ], 404);
});
