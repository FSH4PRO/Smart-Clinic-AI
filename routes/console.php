<?php

declare(strict_types=1);

use App\Console\Commands\AiPredictNoShowCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily AI no-show prediction for upcoming appointments (06:00).
// Note: Laravel 12 uses route-based command registration for scheduling.
Schedule::command(new AiPredictNoShowCommand())
    ->dailyAt('06:00');
