<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessPriceUpdate;
use App\Http\Controllers\Controller;
use App\Models\TradingBot;
use Illuminate\Support\Facades\Log;



class PriceUpdateController extends Controller
{
    public function store(Request $request)
    {
        Log::info("Prijsupdate ontvangen in Laravel:", $request->all()); // Extra log

        $validated = $request->validate([
            'pair' => 'required|string',
            'price' => 'required|numeric',
            'top' => 'required|numeric',
            'botId' => 'required|integer'
        ]);

        $bot = TradingBot::find($validated['botId']);
        if (!$bot) {
            Log::warning("❌ Bot met ID {$validated['botId']} niet gevonden voor pair {$validated['pair']}");
            return response()->json(['error' => 'Bot niet gevonden'], 404);
        }

        // update last price en top in de database naar de net ontvangen gegevens
        $bot->botRun->update(['last_price' => $validated['price']]);
        $bot->botRun->update(['top' => $validated['top']]);
        
        // Start verwerking in de queue met enkel de bot ID om problemen met serialisatie in job te voorkomen
        ProcessPriceUpdate::dispatch($bot->getBotId());

        return response()->json(['message' => 'Prijsupdate ontvangen en verwerking gestart']);
    }
}
  