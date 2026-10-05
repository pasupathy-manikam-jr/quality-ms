<?php

use App\Livewire\Roles\Index;
use Illuminate\Support\Facades\Route;

Route::livewire('roles', Index::class)
    ->middleware('permission:manage-roles')
    ->name('roles.index');
