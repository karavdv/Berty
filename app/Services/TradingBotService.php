<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Services\KrakenApiServicePrivate;

class TradingBotService
{
    protected KrakenApiServicePrivate $krakenApi;
    protected string $pair;
    protected float $threshold;
    protected ?float $lastPrice = null;

    public function __construct(KrakenApiServicePrivate $krakenApi, string $pair, float $threshold)
    {
        $this->krakenApi = $krakenApi;
        $this->pair = $pair;
        $this->threshold = $threshold;
    }

    public function getMarketPrice(): ?float
    {
        try {
            $response = $this->krakenApi->sendRequest('/0/public/Ticker', ['pair' => $this->pair]);

            if (isset($response['result'][$this->pair]['c'][0])) {
                return floatval($response['result'][$this->pair]['c'][0]);
            }
            return null;
        } catch (\Exception $e) {
            Log::error('Fout bij ophalen marktprijs: ' . $e->getMessage());
            return null;
        }
    }

    public function tradeLogic(): void
    {
        try {
            $currentPrice = $this->getMarketPrice();

            if (is_null($currentPrice)) {
                Log::warning('Geen marktprijs beschikbaar.');
                return;
            }

            if (is_null($this->lastPrice)) {
                $this->lastPrice = $currentPrice;
                Log::info("Initialisatie: huidige prijs is €{$currentPrice}");
                return;
            }

            $priceChange = (($currentPrice - $this->lastPrice) / $this->lastPrice) * 100;

            if ($priceChange <= -$this->threshold) {
                Log::info("Prijs gedaald met {$priceChange}%, aankoop gestart...");
                $this->placeBuyOrder(0.01);
            } elseif ($priceChange >= $this->threshold) {
                Log::info("Prijs gestegen met {$priceChange}%, verkoop gestart...");
                $this->placeSellOrder(0.01);
            } else {
                Log::info("Geen actie nodig. Prijsverandering: {$priceChange}%.");
            }

            $this->lastPrice = $currentPrice;
        } catch (\Exception $e) {
            Log::error('Fout in handelslogica: ' . $e->getMessage());
        }
    }

    public function placeBuyOrder(float $volume): void
    {
        $this->placeOrder('buy', $volume);
    }

    public function placeSellOrder(float $volume): void
    {
        $this->placeOrder('sell', $volume);
    }

    protected function placeOrder(string $type, float $volume): void
    {
        try {
            $response = $this->krakenApi->sendRequest('/0/private/AddOrder', [
                'pair' => $this->pair,
                'type' => $type,
                'ordertype' => 'market',
                'volume' => $volume
            ]);

            Log::info(ucfirst($type) . ' order geplaatst:', $response);
        } catch (\Exception $e) {
            Log::error("Fout bij plaatsen {$type} order: " . $e->getMessage());
        }
    }

    public function start(): void
    {
        Log::info('Bot gestart...');
        while (true) {
            $this->tradeLogic();
            sleep(60); // Wacht 60 seconden voor de volgende analyse
        }
    }
}
