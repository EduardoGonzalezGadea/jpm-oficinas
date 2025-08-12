<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Tesoreria\Cajas\Index;

Route::get('/tesoreria/cajas', Index::class)->name('tesoreria.cajas.index');
