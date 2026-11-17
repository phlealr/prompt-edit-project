<?php

use App\Http\Controllers\Generations\CreateGenerationController;
use App\Http\Controllers\Generations\EstimateGenerationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('generate', CreateGenerationController::class)->name('generate');
Route::post('generate/estimate', EstimateGenerationController::class)->name('generate.estimate');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
