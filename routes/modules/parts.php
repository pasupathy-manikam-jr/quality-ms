<?php

use App\Livewire\Parts\Index;
use Illuminate\Support\Facades\Route;

Route::livewire('parts', Index::class)
    ->middleware('permission:manage-parts')
    ->name('parts.index');
