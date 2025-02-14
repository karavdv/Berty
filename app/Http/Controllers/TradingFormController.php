<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Services\CurrencyPairs;

class TradingFormController extends Controller
{
    protected $currencyPairs;

    public function __construct(CurrencyPairs $currencyPairs)
    {
        $this->currencyPairs = $currencyPairs;
    }

    public function fetchCurrencyPairs($currency)
    {
        $pairs = $this->currencyPairs->getPairsForCurrency(strtoupper($currency));//strtoupper() transforms string in uppercase ass extra security against mistakes
        return response()->json($pairs);
    }
}
