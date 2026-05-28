<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Tour;
use App\Models\TourTranslation;
use App\Models\TourPrice;
use App\Models\TourMediaGallery;
use App\Services\CacheService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });

        // Invalidate the public tour-listing cache whenever card-visible data
        // changes. These four models cover everything a listing card shows
        // (active/status/duration/availability/image on Tour, title/slug on
        // TourTranslation, min_price on TourPrice, image on TourMediaGallery).
        // The version bump is guarded to fire once per request even if a save
        // touches many child rows.
        $bump = static fn () => CacheService::bumpToursVersion();
        foreach ([Tour::class, TourTranslation::class, TourPrice::class, TourMediaGallery::class] as $model) {
            $model::saved($bump);
            $model::deleted($bump);
        }
    }
}
