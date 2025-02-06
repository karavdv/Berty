<?php

namespace App\Services;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Promise\Utils;

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

    public function getEuroPairs( string $currency): array
    {
        Log::info('Currency ontvangen in getEuroPairs', ['currency' => $currency]);

        $response = $this->apiConnection->publicRequest('AssetPairs', ['info' => 'leverage']);
        $pairs = $response['result'] ?? [];
        $filteredPairs = [];

        foreach (array_keys($pairs) as $pair) {
            if (str_ends_with($pair, $currency)) {
                $filteredPairs[] = $pair;
            }
        }
    
        return $filteredPairs;
    }

    public function analyzePair(string $pair, array $data, $numberMovements, $changeRequired): ?array
    {
        $ohlcData = $data['result'][$pair] ?? null;
    
        if (!is_array($ohlcData) || empty($ohlcData)) {
            Log::warning("Geen geldige OHLC data voor paar: $pair");
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
        $startValue = (float)$data[0][1];// Start met de Open van de eerste candle

        foreach ($data as $candle) {
            $currentClose = (float)$candle[4];

            if ($currentClose <= 0 || $startValue <= 0) {
                continue;// Sla ongeldige candles over
            }

            $change = (($currentClose - $startValue) / $startValue) * 100;

            if ($change >= $changeRequired) {
                $rises++;
                $startValue = $currentClose;// Reset bij een stijging
            } elseif ($change <= -$changeRequired) {
                $declines++;
                $startValue = $currentClose;// Reset bij een daling
            }
        }


            return ['rises' => $rises, 'declines' => $declines];

    }

    public function findQualifiedPairs(string $currency, int $numberMovements, float $changeRequired): \Generator
    {
        Log::info('findQualifiedPairs() gestart', ['currency' => $currency]);

        $pairs = $this->getEuroPairs($currency);
        Log::info('Aantal paren opgehaald:', ['count' => count($pairs)]);

        $promises = [];

        // Maak asynchrone aanvragen aan
        foreach ($pairs as $pair) {
            $promises[$pair] = $this->apiConnection->publicRequest('OHLC', ['pair' => $pair, 'interval' => 60]);
        }
    
        // Wacht tot alle promises zijn vervuld
        $responses = Utils::settle($promises)->wait();

        if (empty($responses)) {
            Log::error('Geen responses ontvangen voor valutaparen.');
            return;
        }
    
        // Verwerk de resultaten
        foreach ($responses as $pair => $response) {
            if ($response['state'] === 'fulfilled') {
                $data = $response['value']; // Ontvang de API-response
                $analyzed = $this->analyzePair($pair, $data, $numberMovements, $changeRequired);
                if ($analyzed) {
                    yield $analyzed; // Lever het resultaat op via de generator
                }
            } else {
                Log::error("API-aanroep mislukt voor paar: $pair", ['error' => $response['reason']]);
            }
        }
    }
    
}
