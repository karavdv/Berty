<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TradingBot;
use App\Http\Controllers\Controller;


class TradingBotController extends Controller
{
    /**
     * Haal het dashboard op met alle bots en hun transacties.
     */
    public function dashboard()
    {
        // Haal alle bots op met hun gekoppelde transacties
        $bots = TradingBot::with('transactions')->get();

        // Optioneel kun je hier de data transformeren
        // zodat alleen de benodigde velden worden teruggegeven.
        $result = $bots->map(function ($bot) {
            return [
                'id'              => $bot->id,
                'pair'            => $bot->pair,
                'status'          => $bot->status,
                'budget'          => $bot->budget, // Zorg dat dit veld in je model bestaat
                'openTradeVolume' => $bot->open_trade_volume, // Dit veld moet je bijhouden of berekenen
                'profit'          => $bot->profit, // Je kunt hier ook berekende winst plaatsen
                'trades'          => $bot->transactions->map(function ($transaction) {
                    return [
                        'buyTime'  => $transaction->created_at->toDateTimeString(),
                        'buyPrice' => $transaction->price,
                        'volume'   => $transaction->volume,
                        'sold'     => $transaction->sold,
                        'sellTime' => $transaction->sell_time,   // Zorg dat je dit veld in je migratie/model hebt
                        'sellPrice'=> $transaction->sell_price,    // Zorg dat je dit veld in je migratie/model hebt
                    ];
                }),
            ];
        });

        return response()->json($result);
    }

    /**
     * Toggle de status van een bot tussen 'dry-run' en 'live'.
     */
    public function toggle(Request $request, $botId)
    {
        $validated = $request->validate([
            'status' => 'required|in:dry-run,live',
        ]);

        $bot = TradingBot::findOrFail($botId);
        $bot->status = $validated['status'];
        // Past de dry_run flag aan: als status live is, wordt dry_run false.
        $bot->dry_run = ($validated['status'] === 'dry-run');
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

        return response()->json(['message' => 'Bot gestopt', 'bot' => $bot]);
    }
}
