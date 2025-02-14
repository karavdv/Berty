<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Models\TradingBot;
use App\Http\Controllers\Controller;
use App\Services\TradingBotService;
use App\Services\KrakenApiServicePrivate;


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



            if (!$bot->id) {
                Log::error("Trading bot kon niet worden aangemaakt.");
                return response()->json(['error' => 'Kon bot niet opslaan'], 500);
            }

            Log::info("Trading bot succesvol aangemaakt met ID: " . $bot->id);

        // Abonneer de bot met zijn bot-ID en valutapaar in Node.js
        $this->subscribeBot($bot->pair, $bot->id);

            $tradingBot = new TradingBotService(
                $this->krakenApi,
                $validated['pair'],
                $validated['tradeSize'],
                $validated['drop'],
                $validated['profit'],
                $validated['startBuy'],
                $validated['budget'],
                $validated['accumulate'],
                $validated['topEdge'],
                $validated['stopLoss'],
                true  // Dry run mode ingeschakeld
            );


            Log::info("Validatie succesvol: ", $validated);



            $tradingBot->setBot($bot); // Een setter toevoegen zodat de service de bot instantie kent

            Log::info("✅ TradingBotService gekoppeld aan bot-ID: {$bot->id}");

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
                'botId' => $botId, // Bot-ID meesturen!
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
        $bots = TradingBot::with('transactions')->get();

        // Transformeer de bots-data zodat alle benodigde velden aanwezig zijn
        $result = $bots->map(function ($bot) {
            return [
                'id'               => $bot->id,
                'pair'             => $bot->pair,
                'status'           => $bot->status,
                'budget'           => $bot->budget,
                'trade_size'       => $bot->trade_size,
                'drop_threshold'   => $bot->drop_threshold,
                'profit_threshold' => $bot->profit_threshold,
                'start_buy'        => $bot->start_buy,
                'accumulate'       => $bot->accumulate,
                'top_edge'         => $bot->top_edge ?? null,
                'stop_loss'        => $bot->stop_loss ?? null,
                'dry_run'          => $bot->dry_run,
                'openTradeVolume'  => $bot->open_trade_volume ?? 0, // Standaard 0 als het niet bestaat
                'profit'           => $bot->profit ?? 0, // Standaard 0 als er nog geen winst is
                'created_at'       => $bot->created_at->toDateTimeString(),
                'updated_at'       => $bot->updated_at->toDateTimeString(),

                // Transacties die bij de bot horen
                'trades' => $bot->transactions->map(function ($transaction) {
                    return [
                        'buyTime'   => $transaction->created_at->toDateTimeString(),
                        'buyPrice'  => $transaction->price,
                        'volume'    => $transaction->volume,
                        'sold'      => $transaction->sold,
                        'sellTime'  => $transaction->sell_time ?? null,
                        'sellPrice' => $transaction->sell_price ?? null,
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
    public function stopBot(Request $request, $botId)
    {
        $bot = TradingBot::findOrFail($botId);
        $bot->status = 'stopped';
        $bot->save();

        // Hier kun je eventueel extra acties ondernemen, zoals een melding naar de achtergrondjob
        // Als de TradingBotService in een lange job draait, moet deze regelmatig de status van $bot controleren.

        return response()->json($bot);
    }
}
