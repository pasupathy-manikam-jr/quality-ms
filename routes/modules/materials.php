<?php

use App\Livewire\Materials\Index;
use App\Livewire\Materials\Show;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:manage-materials')->group(function () {
    Route::livewire('materials', Index::class)->name('materials.index');
    Route::livewire('materials/{material}', Show::class)->name('materials.show');
});
