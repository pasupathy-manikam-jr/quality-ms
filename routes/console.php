<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('qms:calibration-reminders')->dailyAt('07:00');
Schedule::command('qms:document-review-reminders')->dailyAt('07:05');
