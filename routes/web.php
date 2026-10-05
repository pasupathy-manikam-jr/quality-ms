<?php

use App\Http\Controllers\GuideController;
use App\Http\Controllers\LocaleController;
use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::post('locale', [LocaleController::class, 'update'])->middleware('throttle:30,1')->name('locale.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', Dashboard::class)->name('dashboard');
    Route::get('guide', GuideController::class)->name('guide');

    // One file per module; each declares its own permission middleware.
    foreach (glob(__DIR__.'/modules/*.php') ?: [] as $module) {
        require $module;
    }
});

require __DIR__.'/settings.php';
