<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TradingBotService;
use App\Services\KrakenApiServicePrivate;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;


class TradingController extends Controller
{
    protected KrakenApiServicePrivate $krakenApi;

    public function __construct(KrakenApiServicePrivate $krakenApi)
    {
        $this->krakenApi = $krakenApi;
    }

    public function startBot(Request $request)
    {
        $validated = $request->validate([
            'pair' => 'required|string',
            'threshold' => 'required|numeric|min:0.1',
        ]);

        $tradingBot = new TradingBotService($this->krakenApi, $validated['pair'], $validated['threshold']);

        // Run bot as background process
        dispatch(function () use ($tradingBot) {
            $tradingBot->start();
        });

        return response()->json(['message' => 'Trading bot gestart voor ' . $validated['pair']]);
    }

    public function getOpenOrders()
    {
        try {
            $response = $this->krakenApi->sendRequest('/0/private/OpenOrders');
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Fout bij ophalen open orders: ' . $e->getMessage()], 500);
        }
    }

    public function cancelOrder(Request $request)
    {
        $validated = $request->validate([
            'txid' => 'required|string',
        ]);

        try {
            $response = $this->krakenApi->sendRequest('/0/private/CancelOrder', ['txid' => $validated['txid']]);
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Fout bij annuleren order: ' . $e->getMessage()], 500);
        }
    }

    public function getOrderHistory()
    {
        try {
            $response = $this->krakenApi->sendRequest('/0/private/TradesHistory', [
                'type' => 'all',
                'trades' => false,
                'consolidate_taker' => true
            ]);
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Fout bij ophalen ordergeschiedenis: ' . $e->getMessage()], 500);
        }
    }
}


/*
class TradingController extends Controller
{
    protected $krakenApi;

    public function __construct(KrakenApiServicePrivate $krakenApi)
    {
        $this->krakenApi = $krakenApi;
    }

    public function testApi()
    {
        $response = $this->krakenApi->sendRequest('/0/private/Balance');
        return response()->json($response);
    }
}*/


