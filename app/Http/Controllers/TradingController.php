<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\KrakenApiServicePrivate;
use App\Http\Controllers\Controller;

class TradingController extends Controller
{
    protected KrakenApiServicePrivate $krakenApi;

    public function __construct(KrakenApiServicePrivate $krakenApi)
    {
        $this->krakenApi = $krakenApi;
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
