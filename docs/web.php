<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\PlanillaTurno;

Route::get('/', PlanillaTurno::class)->name('turno.planilla');
