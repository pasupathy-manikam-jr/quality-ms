<?php

use App\Http\Controllers\CertificateFileController;
use App\Livewire\Certificates\Index;
use App\Livewire\Certificates\Show;
use App\Livewire\Lots\Index as LotsIndex;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:manage-certificates')->group(function () {
    Route::livewire('certificates', Index::class)->name('certificates.index');
    Route::livewire('certificates/{certificate}', Show::class)->name('certificates.show');
    Route::get('certificates/{certificate}/file', CertificateFileController::class)->name('certificates.file');
    Route::livewire('lots', LotsIndex::class)->name('lots.index');
});
