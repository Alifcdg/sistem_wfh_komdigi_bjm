<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Otomatisasi WFH setiap hari Jumat
Schedule::command('wfh:generate-friday')
    ->weeklyOn(5, '00:00')
    ->timezone('Asia/Makassar');
