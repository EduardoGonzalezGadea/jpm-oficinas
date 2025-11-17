<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Tesoreria\Valores\Index as ValoresIndex;
use App\Http\Livewire\Tesoreria\Valores\Servicio\Index as ServicioIndex;
use App\Http\Livewire\Tesoreria\Valores\TipoLibreta\Index as TipoLibretaIndex;
use App\Http\Livewire\Tesoreria\Valores\Reportes\Index as ReportesIndex;

Route::prefix('valores')->name('valores.')->group(function () {
    Route::get('/', ValoresIndex::class)->name('index');
    Route::get('/entregas', \App\Http\Livewire\Tesoreria\Valores\Entrega\Index::class)->name('entregas');
    Route::get('/servicios', ServicioIndex::class)->name('servicios');
    Route::get('/tipos-libreta', TipoLibretaIndex::class)->name('tipos-libreta');
    Route::get('/reportes', ReportesIndex::class)->name('reportes');
});
