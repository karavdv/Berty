<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Models\TradingBot;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessPriceUpdate;
use App\Services\TradingBotService;
use App\Services\KrakenApiServicePrivate;
use Illuminate\Support\Facades\Http;



class TradingBotController extends Controller
{
    protected KrakenApiServicePrivate $krakenApi;

    public function __construct(KrakenApiServicePrivate $krakenApi)
    {
        $this->krakenApi = $krakenApi;
    }

    public function startBot(Request $request)
    {
        try {
            $validated = $request->validate([
                'pair'       => 'required|string',
                'tradeSize'  => 'required|numeric|min:0.01',
                'drop'       => 'required|numeric|min:0.1',
                'profit'     => 'required|numeric|min:0.1',
                'startBuy'   => 'required|numeric|min:0.00000001',
                'budget'    => 'required|numeric|min:0.01',
                'accumulate' => 'sometimes|boolean',
                'topEdge'    => 'nullable|numeric|min:0.1',
                'stopLoss'   => 'nullable|numeric|min:0'
            ]);

            Log::info("Te bewaren bot data:", $validated);

            $bot = \App\Models\TradingBot::create([
                'pair' => $validated['pair'],
                'trade_size' => $validated['tradeSize'],
                'drop_threshold' => $validated['drop'],
                'profit_threshold' => $validated['profit'],
                'start_buy' => $validated['startBuy'],
                'budget' => $validated['budget'],
                'accumulate' => $validated['accumulate'] ?? false,
                'top_edge' => $validated['topEdge'] ?? null,
                'stop_loss' => $validated['stopLoss'] ?? null,
                'dry_run' => true,
                'status' => 'active',
            ]);

            $bot->botRun()->create([
                //'bot_id' => $bot->id,
                'last_price' => 0,
                'reference_price' => 0,
                'top' => 0,
                'open_trade_volume' => 0,
                'total_traded_volume' => 0,
                'is_live' => false
            ]);

            if (!$bot->id) {
                Log::error("Trading bot kon niet worden aangemaakt.");
                return response()->json(['error' => 'Kon bot niet opslaan'], 500);
            }

            Log::info("Trading bot succesvol aangemaakt met ID: " . $bot->id);

            // Abonneer de bot met zijn bot-ID en valutapaar in Node.js
            $this->subscribeBot($bot->pair, $bot->id);

            Log::info("Validatie succesvol: ", $validated);

            return response()->json(['message' => 'Trading bot gestart', 'bot' => $bot], 201);
        } catch (\Exception $e) {
            Log::error("Fout bij aanmaken trading bot: " . $e->getMessage());
            return response()->json([
                'error'   => 'Er ging iets mis',
                'message' => $e->getMessage()
            ], 500);
        }
    }

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
            Log::info("✅ Abonnement gestart in Node.js voor bot-ID: {$botId}, pair: {$pair}");

            return $body;
        } catch (\Exception $e) {
            // Log eventuele fouten en handel af
            Log::error("❌ Fout bij abonnement starten voor {$pair}, bot-ID {$botId}: " . $e->getMessage());
            return null;
        }
    }
    /**
     * Haal het dashboard op met alle bots en hun transacties.
     */
    public function dashboard()
    {
        // Haal alle bots op met hun gekoppelde transacties
        $bots = TradingBot::with(['botRun', 'transactions'])->get();

        // Transformeer de bots-data zodat alle benodigde velden aanwezig zijn
        $result = $bots->map(function ($bot) {
            return [
                'id'               => $bot->id,
                'pair'             => $bot->pair,
                'status'           => $bot->status,
                'budget'           => round($bot->budget,2),
                'trade_size'       => round($bot->trade_size,2),
                'drop_threshold'   => $bot->drop_threshold,
                'profit_threshold' => $bot->profit_threshold,
                'start_buy'        => $bot->start_buy,
                'accumulate'       => $bot->accumulate,
                'top_edge'         => $bot->top_edge ?? null,
                'stop_loss'        => $bot->stop_loss ?? null,
                'dry_run'          => $bot->dry_run,
                'created_at'       => $bot->created_at->toDateTimeString(),
                'updated_at'       => $bot->updated_at->toDateTimeString(),

                // Gegevens uit bot_run

                'openTradeVolume'   => round(optional($bot->botRun)->open_trade_volume ?? 0,2),
                'totalTradedVolume' => round(optional($bot->botRun)->total_traded_volume ?? 0,2),
                
                // Transacties die bij de bot horen
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

        return response()->json($result);
    }


    /**
     * Toggle de status van een bot tussen dry-run (true) en live (false).
     */
    public function toggle(Request $request, $botId)
    {
        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);

        $bot = TradingBot::findOrFail($botId);
        // Past de dry_run flag aan: als status live is, wordt dry_run false.
        $bot->dry_run = $validated['status'];
        $bot->save();

        return response()->json($bot);
    }

    /**
     * Stop de bot door de status op 'stopped' te zetten.
     */
    public function stopBot($botId)
    {
        $bot = TradingBot::findOrFail($botId);
        $bot->status = 'stopped';
        $bot->save();

        // Stuur een verzoek naar de WebSocket-service om het abonnement te stoppen
        Http::post('http://127.0.0.1:3000/unsubscribe', [
            'pair' => $bot->pair, // Zorg ervoor dat je valutapaar is opgeslagen in de bot
            'botId' => $bot->id,
        ]);
        return response()->json($bot);
    }


    public function restartBot($botId)
    {
        Log::info("🔍 Opnieuw starten van bot met ID: " . $botId);

        $bot = TradingBot::find($botId);
        if (!$bot) {
            Log::error("❌ Bot niet gevonden: " . $botId);
            return response()->json(['error' => 'Bot niet gevonden'], 404);
        }

        $bot->status = 'active';
        $bot->save();

        Log::info("📡 Probeer bot opnieuw te abonneren op WebSocket");

        try {
            Http::post('http://127.0.0.1:3000/subscribe', [
                'pair' => $bot->pair,
                'botId' => $bot->id,
            ]);
            Log::info("✅ WebSocket-abonnement succesvol gestart voor bot-ID: {$botId}");
        } catch (\Exception $e) {
            Log::error("❌ Fout bij verbinden met WebSocket: " . $e->getMessage());
            return response()->json(['error' => 'Kon WebSocket niet starten'], 500);
        }
        
        return response()->json(['message' => 'Bot opnieuw gestart en geabonneerd op WebSocket.', 'bot' => $bot]);
    }

    /**
     * Verwijder een bot permanent uit de database
     */
    public function deleteBot($botId)
    {
        $bot = TradingBot::find($botId);

        if (!$bot) {
            return response()->json(['error' => 'Bot niet gevonden'], 404);
        }

        $bot->delete();

        try {
            Http::post('http://127.0.0.1:3000/unsubscribe', [
                'pair' => $bot->pair,
                'botId' => $bot->id,
            ]);
            Log::info("❌ WebSocket-abonnement succesvol gestopt voor bot-ID: {$botId}");
        } catch (\Exception $e) {
            Log::error("❌ Fout bij stopzetten van WebSocket: " . $e->getMessage());
            return response()->json(['error' => 'Kon WebSocket niet stoppen'], 500);
        }

        return response()->json(['message' => 'Bot succesvol verwijderd']);
    }

    public function getActiveBots()
    {
        // Haal alle actieve bot-ID's op
        $activeBots = TradingBot::where('status', 'active')->pluck('id');

        return response()->json([
            'botIds' => $activeBots
        ], 200);
    }
}
