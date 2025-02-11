<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Services\MarketAnalyzer;

class TradingFormController extends Controller
{
    protected $marketAnalyzer;

    public function __construct(MarketAnalyzer $marketAnalyzer)
    {
        $this->marketAnalyzer = $marketAnalyzer;
    }

    public function fetchCurrencyPairs($currency)
    {
        $pairs = $this->marketAnalyzer->getCurrencyPairs(strtoupper($currency));//strtoupper() transforms string in uppercase ass extra security against mistakes
        return response()->json($pairs);
    }
}
