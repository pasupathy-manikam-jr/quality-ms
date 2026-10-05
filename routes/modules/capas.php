<?php

use App\Http\Controllers\PrintController;
use App\Livewire\Capas\Index;
use App\Livewire\Capas\Show;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:manage-capas')->group(function () {
    Route::livewire('capas', Index::class)->name('capas.index');
    Route::livewire('capas/{capa}', Show::class)->name('capas.show');
    Route::get('capas/{capa}/print', [PrintController::class, 'capa'])->name('capas.print');
});
