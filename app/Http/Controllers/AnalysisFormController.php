<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Jobs\FindQualifiedPairsJob;

class AnalysisFormController extends Controller
{
    public function analiseCurrencyPairs(Request $request)
    {
        // Wis de cache voordat een nieuwe analyse start
        Cache::forget('qualified_pairs');

        // Validatie van de invoer
        $validated = $request->validate([
            'currency' => 'required',
            'numberMovements' => 'required|integer|min:1',
            'change' => 'required|numeric|min:0.01',
        ]);

        // Gegevens uit React formulier ophalen
        $currency = $validated['currency'];
        $numberMovements = (int) $validated['numberMovements'];
        $change = (float) $validated['change'];

        // Dispatch de job met parameters
        FindQualifiedPairsJob::dispatch($currency, $numberMovements, $change);

        return response()->json(['message' => 'Analysis started'], 202);
    }
}
