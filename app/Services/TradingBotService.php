<?php

namespace App\Services;

use App\Models\TradingBot;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;
use App\Services\KrakenApiServicePrivate;

class TradingBotService
{
    protected Client $httpClient;
    protected KrakenApiServicePrivate $krakenApi;

    public function __construct(KrakenApiServicePrivate $krakenApi)
    {
        $this->httpClient = new Client(['timeout' => 10]); // Set a timeout for API calls
        $this->krakenApi = $krakenApi;
    }

    /**
     * Create a new trading bot and initialize its run data.
     */
    public function createBot(array $validatedData)
    {
        $bot = TradingBot::create([
            'pair'             => $validatedData['pair'],
            'trade_size'       => $validatedData['tradeSize'],
            'drop_threshold'   => $validatedData['drop'],
            'profit_threshold' => $validatedData['profit'],
            'start_buy'        => $validatedData['startBuy'],
            'budget'           => $validatedData['budget'],
            'accumulate'       => $validatedData['accumulate'] ?? false,
            'top_edge'         => $validatedData['topEdge'] ?? null,
            'stop_loss'        => $validatedData['stopLoss'] ?? null,
            'dry_run'          => true,
            'status'           => 'active',
        ]);

        // Initialize the bot run data
        $bot->botRun()->create([
            'last_price'          => 0,
            'reference_price'     => 0,
            'top'                 => 0,
            'workbudget'          => $validatedData['budget'],
            'open_trade_volume'   => 0,
            'total_traded_volume' => 0,
            'is_live'             => false
        ]);


        if (!$bot->id) {
            Log::error("Failed to create trading bot.");
            throw new Exception("Failed to create the bot.");
        }

        Log::info("Trading bot successfully created with ID: " . $bot->id);

        return $bot;
    }

    /**
     * Subscribe the bot to the Node.js WebSocket service.
     */
    public function subscribeBot($pair, $botId)
    {
        $client = new Client();
        try {
            $response = $client->post('http://127.0.0.1:3000/subscribe', [
                'json' => [
                    'pair'  => $pair,
                    'botId' => $botId,
                ],
                'timeout' => 10,
            ]);

            $body = json_decode($response->getBody(), true);
            Log::info("✅ Websocket subscription started for bot-ID: {$botId}, pair: {$pair}");

            return $body;
        } catch (\Exception $e) {
            Log::error("❌ Error starting websocket subscription for {$pair}, bot-ID {$botId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieves all bots and their transactions to show on the dashboard
     */
    public function dashboard()
    {
        // Retrieve all bots with associated botRun and transactions
        $bots = TradingBot::with(['botRun', 'transactions'])->get();

        // Transform bot-data for front-end
        $result = $bots->map(function ($bot) {
            return [
                'id'               => $bot->id,
                'pair'             => $bot->pair,
                'status'           => $bot->status,
                'budget'           => round($bot->budget, 2),
                'trade_size'       => round($bot->trade_size, 2),
                'drop_threshold'   => $bot->drop_threshold,
                'profit_threshold' => $bot->profit_threshold,
                'start_buy'        => $bot->start_buy,
                'accumulate'       => $bot->accumulate,
                'top_edge'         => $bot->top_edge ?? null,
                'stop_loss'        => $bot->stop_loss ?? null,
                'dry_run'          => $bot->dry_run,
                'created_at'       => $bot->created_at->toDateTimeString(),
                'updated_at'       => $bot->updated_at->toDateTimeString(),

                // Data from botRun
                'workbudget'          => round($bot->botRun->workbudget, 2),
                'openTradeVolume'   => round(optional($bot->botRun)->open_trade_volume ?? 0, 2),
                'totalTradedVolume' => round(optional($bot->botRun)->total_traded_volume ?? 0, 2),
                'profit' => round(optional($bot->botRun)->profit ?? 0, 2),

                // Transactions
                'trades' => $bot->transactions->map(function ($transaction) {
                    return [
                        'buyTime'   => $transaction->created_at->toDateTimeString(),
                        'buyPrice'  => $transaction->price,
                        'volume'    => $transaction->volume,
                        'sold'      => $transaction->sold,
                        'sellTime'  => $transaction->sold_at ? $transaction->sold_at->toDateTimeString() : null, // Gebruik de nieuwe timestamp
                        'sellAmount' => $transaction->sell_amount ?? null,
                    ];
                }),
            ];
        });

        return $result;
    }

    /**
     * Status toggle; dry-run (true) and live (false).
     */
    public function toggle(bool $status, $botId)
    {
        $bot = TradingBot::findOrFail($botId);
        $bot->dry_run = $status;
        $bot->save();

        return $bot;
    }

    /**
     * Ends bot activity by changing status in database. The websocket subscription is terminated.
     */
    public function stopBot($botId)
    {
        $bot = TradingBot::findOrFail($botId);
        $bot->status = 'stopped';
        $bot->save();

        // Sends request to the WebSocket-service to terminate the subscription.
        Http::post('http://127.0.0.1:3000/unsubscribe', [
            'pair' => $bot->pair,
            'botId' => $bot->id,
        ]);
        return $bot;
    }

    /**
     * After stopping the bot, it can be restarted. The status is updated in the database and the subscription to the websocket is restarted.
     */
    public function restartBot($botId)
    {
        Log::info("🔍 Restarting bot with ID: " . $botId);

        $bot = TradingBot::find($botId);
        if (!$bot) {
            Log::error("❌ Bot not found: " . $botId);
            return response()->json(['error' => 'Bot not found'], 404);
        }

        $bot->status = 'active';
        $bot->save();

        try {
            // Sends request to the WebSocket-service to restart the subscription.
            Http::post('http://127.0.0.1:3000/subscribe', [
                'pair' => $bot->pair,
                'botId' => $bot->id,
            ]);
            Log::info("✅ WebSocket-subscription started for bot-ID: {$botId}");
        } catch (\Exception $e) {
            Log::error("❌ Error while terying to subscribe to WebSocket: " . $e->getMessage());
            return response()->json(['error' => 'Could not start subscription to WebSocket'], 500);
        }

        return $bot;
    }

    /**
     * Remove a bot permanently from the database
     */
    public function deleteBot($botId)
    {
        $bot = TradingBot::find($botId);

        if (!$bot) {
            return response()->json(['error' => 'Bot not found'], 404);
        }

        $bot->delete();

        try {
            Http::post('http://127.0.0.1:3000/unsubscribe', [
                'pair' => $bot->pair,
                'botId' => $bot->id,
            ]);
            Log::info("❌ WebSocket subscription succesfully ended for bot-ID: {$botId}");
        } catch (\Exception $e) {
            Log::error("❌ Error terminating WebSocket subscription: " . $e->getMessage());
            return response()->json(['error' => 'Could not terminate subscription to WebSocket'], 500);
        }

        return ['message' => 'Bot succesfully deleted'];
    }

    public function getActiveBots()
    {
        // Retrieve all active bot-ID's
        $activeBots = TradingBot::where('status', 'active')->pluck('id');

        return [
            'botIds' => $activeBots
        ];
    }
}
