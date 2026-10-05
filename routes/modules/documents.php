<?php

use App\Http\Controllers\DocumentRevisionFileController;
use App\Livewire\Documents\Index;
use App\Livewire\Documents\ReadingList;
use App\Livewire\Documents\Show;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:manage-documents')->group(function () {
    Route::livewire('documents', Index::class)->name('documents.index');
    Route::livewire('documents/{document}', Show::class)->name('documents.show');
});

// Every signed-in user: their own reading list, and files they were asked to read (checked in the controller).
Route::livewire('reading-list', ReadingList::class)->name('reading-list');
Route::get('document-revisions/{revision}/file', DocumentRevisionFileController::class)->name('document-revisions.file');
