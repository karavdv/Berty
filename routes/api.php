<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalculateGoalController;
use App\Http\Controllers\AnalysisFormController;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\TradingController;
use App\Http\Controllers\TradingFormController;
use App\Http\Controllers\TradingBotController;
use App\Http\Controllers\PriceUpdateController;
use App\Services\CurrencyPairs;

Route::post('/goal', [CalculateGoalController::class, 'calculateInvestment']);

/* routes analiser */
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



/* routes trading */

/* form */
Route::get('/currency-pairs/{currency}', [CurrencyPairs::class, 'getPairsForCurrency']);

/* trading */
Route::post('/trading-bot/start', [TradingBotController::class, 'startBot']);

Route::post('/trading/start', [TradingController::class, 'startBot']);
Route::get('/trading/dry-run-orders', [TradingController::class, 'getDryRunTrades']);

Route::post('/price-update', [PriceUpdateController::class, 'store']);


Route::get('/trading/open-orders', [TradingController::class, 'getOpenOrders']);
Route::post('/trading/cancel', [TradingController::class, 'cancelOrder']);
Route::get('/trading/history', [TradingController::class, 'getOrderHistory']);



/* routes dashboard */

Route::get('/trading/dashboard', [TradingBotController::class, 'dashboard']);
Route::post('/trading/{botId}/toggle', [TradingBotController::class, 'toggle']);
Route::post('/trading/{botId}/stop', [TradingBotController::class, 'stopBot']);

