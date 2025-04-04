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

        // update last price and top in database to new data
        $bot->botRun->update(['last_price' => $validated['price']]);
       
        // Retrieve the current top value
        $currentTop = $bot->botRun->top;
        Log::info("Current top value: {$currentTop}");
        // Update only if the new value is not the same as the current one
        // Using a small margin of error to compare floating point numbers (111.11 and 111.1100000 are not seen as equal with !== check)
        $epsilon = 0.00000001; // Define margin of error
        if (abs($validated['top'] - $currentTop) > $epsilon) {
            $bot->botRun->update(['top' => $validated['top']]);
            Log::info("Top value updated to: {$validated['top']}");
        }

        // Start processing queue with only bot ID instead of bot entity to prevent issues with serialisation.
        ProcessPriceUpdate::dispatch($bot->getBotId());

        return response()->json(['message' => 'Priceupdate received and processing started']);
    }
}
