<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('workshop:cancel-expired-appointments')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('payments:expire-unpaid-card-orders')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('workshop:remind-upcoming-appointments')
    ->dailyAt('08:00')
    ->withoutOverlapping();
