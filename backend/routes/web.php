<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// The server-rendered Livewire/Blade admin was removed (nobody used it — the
// real admin is the Nuxt app on Vercel talking to /api). What remains here is
// the minimal web surface: welcome, the Breeze auth scaffold (route('login')
// is still the redirect target when a browser hits a protected API route)
// and the profile pages that scaffold ships with.

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

require __DIR__.'/auth.php';
