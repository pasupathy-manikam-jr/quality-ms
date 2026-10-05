<?php

use App\Livewire\Suppliers\Index;
use Illuminate\Support\Facades\Route;

Route::livewire('suppliers', Index::class)
    ->middleware('permission:manage-suppliers')
    ->name('suppliers.index');
