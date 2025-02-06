<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalculateGoalController;
use App\Http\Controllers\AnalysisFormController;
use Illuminate\Support\Facades\Cache;

Route::post('/goal', [CalculateGoalController::class, 'calculateInvestment']);

Route::post('/analyze', [AnalysisFormController::class, 'analiseCurrencyPairs']);

Route::get('/analyze/results', function () {
    if (Cache::has('qualified_pairs')) {
        return response()->json([
            'status' => 'complete',
            'results' => Cache::get('qualified_pairs'),
        ]);
    }

    return response()->json(['status' => 'pending', 'results' => []]);
});
