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
        Log::info("Price update received in PriceUpdateController:", $request->all());

        $validated = $request->validate([
            'pair' => 'required|string',
            'price' => 'required|numeric',
            'top' => 'required|numeric',
            'botId' => 'required|integer'
        ]);

        $bot = TradingBot::find($validated['botId']);
        if (!$bot) {
            Log::warning("❌ Bot with ID {$validated['botId']} not found for pair {$validated['pair']}");
            return response()->json(['error' => 'Bot not found'], 404);
        }

        // update last price en top in de database naar de net ontvangen gegevens
        $bot->botRun->update(['last_price' => $validated['price']]);
       
        // Retrieve the current top value
        $currentTop = $bot->botRun->top;
        // Update only if the new value is higher
        if ($validated['top'] > $currentTop) {
            $bot->botRun->update(['top' => $validated['top']]);
        }

        // Start processing queue with only bot ID instead of bot entity to prevent issues with serialisation.
        ProcessPriceUpdate::dispatch($bot->getBotId());

        return response()->json(['message' => 'Priceupdate received and processing started']);
    }
}
