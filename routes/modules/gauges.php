<?php

use App\Http\Controllers\CalibrationFileController;
use App\Livewire\Gauges\Index;
use App\Livewire\Gauges\Show;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:manage-gauges')->group(function () {
    Route::livewire('gauges', Index::class)->name('gauges.index');
    Route::livewire('gauges/{gauge}', Show::class)->name('gauges.show');
    Route::get('calibrations/{calibration}/file', CalibrationFileController::class)->name('calibrations.file');
});
