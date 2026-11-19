<?php

use App\Models\EventoAplicacion;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auditoría del administrador: borra los eventos informativos de más de 180 días.
Schedule::command('model:prune', ['--model' => [EventoAplicacion::class]])->daily();
