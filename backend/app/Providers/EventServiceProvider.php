<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

use App\Events\TourCreated;
use App\Events\TourUpdated;
use App\Events\BookingCreated;
use App\Events\BookingConfirmed;

use App\Listeners\ProcessNewTour;
use App\Listeners\UpdateTourCache;
use App\Listeners\SendBookingNotification;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        TourCreated::class => [
            ProcessNewTour::class,
        ],

        TourUpdated::class => [
            UpdateTourCache::class,
        ],

        BookingCreated::class => [
            SendBookingNotification::class,
        ],

        BookingConfirmed::class => [
            SendBookingNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}