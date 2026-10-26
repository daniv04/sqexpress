<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('packages:sync-statuses')
    ->everyFifteenMinutes()
    ->withoutOverlapping(10);

Schedule::command('packages:flag-pending-warehouse-reception')
    ->hourly()
    ->withoutOverlapping(50);
