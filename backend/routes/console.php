<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Clean up abandoned checkouts (unpaid pending bookings) every hour.
Schedule::command('bookings:expire-pending --hours=24')->hourly();
