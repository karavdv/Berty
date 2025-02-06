<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');

/*Route::get('/', function () {

    return view('welcome');
});*/



use App\Http\Controllers\TradingController;

Route::get('/api/test', [TradingController::class, 'testApi']);


