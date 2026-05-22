<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Purga automática de la papelera: borra definitivamente elementos
// que llevan más de PAPELERA_DIAS_RETENCION días (por defecto 30).
Schedule::command('papelera:purgar')->dailyAt('02:00');
