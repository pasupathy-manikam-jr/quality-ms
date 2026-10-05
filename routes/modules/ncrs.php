<?php

use App\Http\Controllers\PrintController;
use App\Livewire\Ncrs\Index;
use App\Livewire\Ncrs\Show;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:manage-ncrs')->group(function () {
    Route::livewire('ncrs', Index::class)->name('ncrs.index');
    Route::livewire('ncrs/{ncr}', Show::class)->name('ncrs.show');
    Route::get('ncrs/{ncr}/print', [PrintController::class, 'ncr'])->name('ncrs.print');
});
