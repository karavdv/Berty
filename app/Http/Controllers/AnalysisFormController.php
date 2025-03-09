<?php
// controller for AnalysisForm.jsx

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Jobs\FindQualifiedPairsJob;

class AnalysisFormController extends Controller
{
    public function analiseCurrencyPairs(Request $request)
    {
        // Delete the cache before a new analysis is started
        Cache::forget('qualified_pairs');

        // Validation input form
        $validated = $request->validate([
            'currency' => 'required',
            'numberMovements' => 'required|integer|min:1',
            'change' => 'required|numeric|min:0.01',
        ]);

        // Retrieving data from form
        $currency = $validated['currency'];
        $numberMovements = (int) $validated['numberMovements'];
        $change = (float) $validated['change'];

        // Dispatch job with parameters. Job will create a new instance of the service MarketAnalyzer.php.
        FindQualifiedPairsJob::dispatch($currency, $numberMovements, $change);

        return response()->json(['message' => 'Analysis started'], 202);
    }
}
