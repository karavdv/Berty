<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TradingBotService;
use App\Services\KrakenApiServicePrivate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;

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
            'pair'       => 'required|string',
            'tradeSize'  => 'required|numeric|min:0.01',
            'drop'       => 'required|numeric|min:0.1',
            'profit'     => 'required|numeric|min:0.1',
            'startBuy'   => 'required|numeric|min:0.01',
            'maxBuys'    => 'required|numeric|min:0.01',
            'accumulate' => 'sometimes|boolean',
            'topEdge'    => 'sometimes|numeric|min:0.1',
            'stopLoss'   => 'sometimes|numeric|min:0'
        ]);

                // Geef default waarden voor optionele parameters
                $accumulate = $validated['accumulate'] ?? false;
                $topEdge    = $validated['topEdge'] ?? null;
                $stopLoss   = $validated['stopLoss'] ?? null;


                $bot = \App\Models\TradingBot::create([
                    'pair'          => $validated['pair'],
                    'trade_size'    => $validated['tradeSize'],
                    'drop_threshold'=> $validated['drop'],
                    'profit_threshold' => $validated['profit'],
                    'start_buy'     => $validated['startBuy'],
                    'max_buys'      => $validated['maxBuys'],
                    'accumulate'    => $accumulate,
                    'top_edge'      => $topEdge,
                    'stop_loss'     => $stopLoss,
                    'dry_run'       => true,
                ]);

        $tradingBot = new TradingBotService($this->krakenApi, 
        $validated['pair'],
            $validated['tradeSize'],
            $validated['drop'],
            $validated['profit'],
            $validated['startBuy'],
            $validated['maxBuys'],
            $accumulate,
            $topEdge,
            $stopLoss,
            true  // Dry run mode ingeschakeld
    );

    $bot = \App\Models\TradingBot::create([
        'pair'           => $validated['pair'],
        'trade_size'     => $validated['tradeSize'],
        'drop_threshold' => $validated['drop'],
        'profit_threshold' => $validated['profit'],
        'start_buy'      => $validated['startBuy'],
        'max_buys'       => $validated['maxBuys'],
        'accumulate'     => $validated['accumulate'] ?? false,
        'top_edge'       => $validated['topEdge'] ?? null,
        'stop_loss'      => $validated['stopLoss'] ?? null,
        'dry_run'        => true,
        'status'         => 'active',
        // Zorg er eventueel voor dat je ook een veld voor budget of openTradeVolume beheert
    ]);
    

    $tradingBot->setBot($bot); // Een setter toevoegen zodat de service de bot instantie kent


        return response()->json(['message' => 'Trading bot gestart voor ' . $validated['pair']]);
    }

    // Endpoint om de gesimuleerde trades op te halen (optioneel)
    public function getDryRunTrades()
    {
        $trades = Cache::get('dry_run_trades', []);
        return response()->json($trades);
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
