<?php

use App\Livewire\Audits\Index;
use App\Livewire\Audits\Show;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:manage-audits')->group(function () {
    Route::livewire('audits', Index::class)->name('audits.index');
    Route::livewire('audits/{audit}', Show::class)->name('audits.show');
});
