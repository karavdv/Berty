<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CurrencyPairs
{
    private KrakenApiServicePublic $apiConnection;

    public function __construct(KrakenApiServicePublic $apiConnection)
    {
        $this->apiConnection = $apiConnection;
    }

    //retrieve all valuta pairs and save in cache for 24 hours
    public function getAllCurrencyPairs(): array
    {
        $cacheKey = "all_currency_pairs";

        // Check if pairs are already saved in cache 
        if (Cache::has($cacheKey)) {
            Log::info("🔄 use cached valuta pairs");
            return Cache::get($cacheKey);
        }

        // If not retreive pairs with Kraken API
        $response = $this->apiConnection->publicRequest('AssetPairs');
        $pairs = $response['result'] ?? [];

        if (empty($pairs)) {
            Log::error("❌ No valuta pairs received from Kraken API.");
            return [];
        }

        // Format valuta pairs with wsname and change XBT -> BTC
        $formattedPairs = [];
        foreach ($pairs as $pair => $details) {
            if (!isset($details['wsname'])) {
                continue;
            }

            $wsname = str_replace("XBT", "BTC", $details['wsname']);

            $formattedPairs[$wsname] = [
                'raw' => $pair, // Origineal API-notation
                'wsname' => $wsname, // Formated pair notation needed for front-end and subscription to websocket price updates
            ];
        }

        // Save data in cache for 24h 
        Cache::put($cacheKey, $formattedPairs, now()->addHours(24));

        Log::info("✅ Valuta pairs saved in cache.");
        return $formattedPairs;
    }

    //Filters currency pairs from cache based on the selected quote currency by comparing the last characters of wsname.
    public function getPairsForCurrency(string $currency): array
    {
        $allPairs = $this->getAllCurrencyPairs();
        $filteredPairs = [];

        foreach ($allPairs as $pair) {
            if (str_ends_with($pair['wsname'], "/$currency")) {
                $filteredPairs[] = $pair['wsname'];
            }
        }

        return $filteredPairs;
    }
}
