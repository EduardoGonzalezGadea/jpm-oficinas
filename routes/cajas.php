<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Tesoreria\Cajas\Index;
use App\Http\Controllers\Tesoreria\ArrendamientoController;

Route::get('/tesoreria/cajas', Index::class)->name('tesoreria.cajas.index');
Route::get('tesoreria/arrendamientos', [ArrendamientoController::class, 'index'])->name('tesoreria.arrendamientos.index');
