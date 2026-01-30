<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Автоматическая проверка здоровья проектов каждые 5 минут
Schedule::command('projects:check-health')
    ->everyFiveMinutes()
    ->withoutOverlapping();
