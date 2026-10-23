<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// needs `php artisan schedule:run` every minute (Coolify scheduled task, infra/coolify/open-invoice.sh)
Schedule::command('invoices:run-schedules')->dailyAt('06:00')->withoutOverlapping();
