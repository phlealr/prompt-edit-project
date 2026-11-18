<?php

use App\Http\Controllers\Credits\BuyCreditsController;
use App\Http\Controllers\Credits\ShowCreditsController;
use App\Http\Controllers\Generations\CreateGenerationController;
use App\Http\Controllers\Generations\EstimateGenerationController;
use App\Http\Controllers\Generations\ListGenerationsController;
use App\Http\Controllers\Generations\StoreGenerationController;
use App\Http\Controllers\Reports\ShowOperatorReportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome', [
    'markupPercent' => (int) config('credits.markup_percent'),
]))->middleware('guest')->name('home');

Route::get('generate', CreateGenerationController::class)->name('generate');
Route::post('generate/estimate', EstimateGenerationController::class)->name('generate.estimate');
Route::post('generations', StoreGenerationController::class)->name('generations.store');
Route::get('history', ListGenerationsController::class)->name('history');
Route::get('credits', ShowCreditsController::class)->name('credits');
Route::post('credits', BuyCreditsController::class)->name('credits.store');
Route::get('operator-report', ShowOperatorReportController::class)->name('operator-report');

require __DIR__.'/settings.php';
