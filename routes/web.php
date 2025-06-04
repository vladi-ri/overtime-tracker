<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TimeEntryController;

Route::get('/', function () {
    return redirect()->route('time-entry.create');
})->name('home');

Route::get('/time-entry', [TimeEntryController::class, 'create'])->name('time-entry.create');
Route::post('/time-entry', [TimeEntryController::class, 'store'])->name('time-entry.store');
Route::get('/time-entry/{id}/edit', [TimeEntryController::class, 'edit'])->name('time-entry.edit');
Route::put('/time-entry/{id}', [TimeEntryController::class, 'update'])->name('time-entry.update');
Route::delete('/time-entry/{id}', [TimeEntryController::class, 'destroy'])->name('time-entry.destroy');