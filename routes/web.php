<?php

use App\Http\Controllers\Generations\CreateGenerationController;
use App\Http\Controllers\Generations\EstimateGenerationController;
use App\Http\Controllers\Generations\ListGenerationsController;
use App\Http\Controllers\Generations\StoreGenerationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('generate', CreateGenerationController::class)->name('generate');
Route::post('generate/estimate', EstimateGenerationController::class)->name('generate.estimate');
Route::post('generations', StoreGenerationController::class)->name('generations.store');
Route::get('history', ListGenerationsController::class)->name('history');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
