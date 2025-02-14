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
            'botId' => 'required|integer'
        ]);

        $bot = TradingBot::find($validated['botId']);
        if (!$bot) {
            Log::warning("❌ Bot met ID {$validated['botId']} niet gevonden voor pair {$validated['pair']}");
            return response()->json(['error' => 'Bot niet gevonden'], 404);
        }
    
        Log::info("📊 Prijsupdate verwerkt voor bot-ID {$bot->id}: prijs: {$validated['price']}");
    
        // Start verwerking in de queue
        ProcessPriceUpdate::dispatch($validated['botId'], $validated['price']);
    
        return response()->json(['message' => 'Prijsupdate ontvangen en verwerkt']);
}

}