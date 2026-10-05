<?php

use App\Livewire\Inspections\Index;
use App\Livewire\Inspections\Show;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:manage-inspections')->group(function () {
    Route::livewire('inspections', Index::class)->name('inspections.index');
    Route::livewire('inspections/{inspection}', Show::class)->name('inspections.show');
});
