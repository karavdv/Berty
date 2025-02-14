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





 /**
     * ✅ Haal alle valutaparen op en sla ze in cache op
     */
    public function getAllCurrencyPairs(): array
    {
        $cacheKey = "all_currency_pairs";

        // Controleer of de paren al in cache staan
        if (Cache::has($cacheKey)) {
            Log::info("🔄 Gebruik cached valutaparen");
            return Cache::get($cacheKey);
        }

        // 📡 Haal de valutaparen op via Kraken API
        $response = $this->apiConnection->publicRequest('AssetPairs');
        $pairs = $response['result'] ?? [];

        if (empty($pairs)) {
            Log::error("❌ Geen valutaparen ontvangen van Kraken API.");
            return [];
        }

        // ✅ Formatteer valutaparen met wsname en vervang XBT → BTC
        $formattedPairs = [];
        foreach ($pairs as $pair => $details) {
            if (!isset($details['wsname'])) {
                continue; // Sla over als wsname ontbreekt
            }

            $wsname = str_replace("XBT", "BTC", $details['wsname']); // XBT → BTC omzetten

            $formattedPairs[$wsname] = [
                'raw' => $pair, // Originele API-notatie
                'wsname' => $wsname, // Correcte valutapaar notatie
            ];
        }

        // 💾 Sla de geformatteerde paren in cache op voor 24 uur
        Cache::put($cacheKey, $formattedPairs, now()->addHours(24));

        Log::info("✅ Valutaparen opgeslagen in cache voor 24 uur.");
        return $formattedPairs;
    }

    /**
     * ✅ Filtert valutaparen uit cache op basis van de geselecteerde quote currency.
     *    → Vergelijkt enkel de laatste tekens van wsname.
     */
    public function getPairsForCurrency(string $currency): array
    {
        $allPairs = $this->getAllCurrencyPairs();
        $filteredPairs = [];

        foreach ($allPairs as $wsname => $details) {
            if (str_ends_with($wsname, "/$currency")) {
                $filteredPairs[] = $wsname;
            }
        }

        return $filteredPairs;
    }
}