<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalculateGoalController;
use App\Http\Controllers\AnalysisFormController;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\TradingController;
use App\Http\Controllers\TradingBotController;
use App\Http\Controllers\PriceUpdateController;
use App\Services\CurrencyPairs;

/* routes goalsetting on homepage */
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

/* routes currency pairs forms*/
Route::get('/currency-pairs/{currency}', [CurrencyPairs::class, 'getPairsForCurrency']);

/* routes bot */
Route::post('/trading-bot/start', [TradingBotController::class, 'startBot']);
Route::get('/active-bots', [TradingBotController::class, 'getActiveBots']);
Route::post('/trading/{botId}/stop', [TradingBotController::class, 'stopBot']);
Route::post('/trading/{botId}/restart', [TradingBotController::class, 'restartBot']);
Route::delete('/trading/{botId}/delete', [TradingBotController::class, 'deleteBot']);

/* routes dashboard */
Route::get('/trading/dashboard', [TradingBotController::class, 'dashboard']);
Route::post('/trading/{botId}/toggle', [TradingBotController::class, 'toggle']);

/* routes trading */
Route::post('/price-update', [PriceUpdateController::class, 'store']);
Route::get('/trading/open-orders', [TradingController::class, 'getOpenOrders']);
Route::post('/trading/cancel', [TradingController::class, 'cancelOrder']);
Route::get('/trading/history', [TradingController::class, 'getOrderHistory']);