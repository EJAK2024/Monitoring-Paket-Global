<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('countries:fetch-all')->weekly()->sundays()->at('02:00');
Schedule::command('wpi:import')->monthly()->sundays()->at('03:00');
Schedule::command('aisstream:refresh')->everyMinute();
Schedule::command('alerts:generate')->hourly();
