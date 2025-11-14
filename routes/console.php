<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule auto-cancel expired bookings every 5 minutes
Schedule::command('bookings:cancel-expired')->everyFiveMinutes();

// Schedule booking status updates every 10 minutes
Schedule::command('booking:update-status')->everyTenMinutes();
