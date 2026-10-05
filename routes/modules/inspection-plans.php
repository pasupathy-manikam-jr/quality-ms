<?php

use App\Livewire\InspectionPlans\Index;
use App\Livewire\InspectionPlans\Show;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:manage-inspection-plans')->group(function () {
    Route::livewire('inspection-plans', Index::class)->name('inspection-plans.index');
    Route::livewire('inspection-plans/{plan}', Show::class)->name('inspection-plans.show');
});
