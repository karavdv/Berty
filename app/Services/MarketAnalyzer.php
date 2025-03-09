<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Promise\Utils;
use App\Services\CurrencyPairs;

class MarketAnalyzer
{
    private KrakenApiServicePublic $apiConnection;
    public string $currency;
    public int $numberMovements;
    public float $changeRequired;

    public function __construct(KrakenApiServicePublic $apiConnection)
    {
        $this->apiConnection = $apiConnection;
    }

    public function setup(string $currency, int $numberMovements, float $changeRequired): void
    {
        $this->currency = $currency;
        $this->numberMovements = $numberMovements;
        $this->changeRequired = $changeRequired;
    }

    public function analyzePair(string $pair, array $data, $numberMovements, $changeRequired): ?array
    {
        $ohlcData = $data['result'][$pair] ?? null;

        if (!is_array($ohlcData) || empty($ohlcData)) {
            Log::warning("No valid OHLC data for pair $pair");
            return null;
        }

        $changes = $this->calculateChanges($ohlcData, $changeRequired);
        if ($changes['rises'] > $numberMovements && $changes['declines'] > $numberMovements) {
            return array_merge(['pair' => $pair], $changes);
        }

        return null;
    }

    private function calculateChanges(array $data, $changeRequired): array|null
    {
        $rises = 0;
        $declines = 0;
        $startValue = (float)$data[0][1]; // Start with Open from the first candle

        foreach ($data as $candle) {
            $currentClose = (float)$candle[4];

            if ($currentClose <= 0 || $startValue <= 0) {
                continue; // Skip invalid candles
            }

            $change = (($currentClose - $startValue) / $startValue) * 100;

            if ($change >= $changeRequired) {
                $rises++;
                $startValue = $currentClose; // Reset with rise
            } elseif ($change <= -$changeRequired) {
                $declines++;
                $startValue = $currentClose; // Reset with decline
            }
        }


        return ['rises' => $rises, 'declines' => $declines];
    }

    public function findQualifiedPairs(string $currency, int $numberMovements, float $changeRequired): \Generator
    {
        Log::info('findQualifiedPairs() started', ['currency' => $currency]);
        
        $currencyPairs = new CurrencyPairs($this->apiConnection);
        $pairs = $currencyPairs->getPairsForCurrency($currency);
        
        Log::info('Pairs retrieved:', ['count' => count($pairs)]);

        $promises = [];

        // Create asynchronous request
        foreach ($pairs as $pair) {
            $promises[$pair] = $this->apiConnection->publicRequest('OHLC', ['pair' => $pair, 'interval' => 60]);
        }

        // Wait until all promises are fulfilled
        $responses = Utils::settle($promises)->wait();

        if (empty($responses)) {
            Log::error('Geen responses ontvangen voor valutaparen.');
            return;
        }

        // Process results
        foreach ($responses as $pair => $response) {
            if ($response['state'] === 'fulfilled') {
                $data = $response['value']; // Receive API-response
                $analyzed = $this->analyzePair($pair, $data, $numberMovements, $changeRequired);
                if ($analyzed) {
                    yield $analyzed; // Deliver the result via the generator
                }
            } else {
                Log::error("API call failed for pair $pair", ['error' => $response['reason']]);
            }
        }
    }
}
