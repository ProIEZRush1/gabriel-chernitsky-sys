<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Genera automáticamente las rentas (cuentas por cobrar) de cada arrendatario
// el día 1 de cada mes. También se puede generar manualmente desde el panel.
Schedule::command('rentas:generar-mensualidades')
    ->monthlyOn(1, '00:30')
    ->withoutOverlapping();
