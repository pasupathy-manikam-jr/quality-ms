<?php

use App\Livewire\Users\Index;
use Illuminate\Support\Facades\Route;

Route::livewire('users', Index::class)
    ->middleware('permission:manage-users')
    ->name('users.index');
