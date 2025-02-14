<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\KrakenApiServicePrivate;

class TradingBotService
{
    protected KrakenApiServicePrivate $krakenApi;
    protected $bot; // Referentie naar het TradingBot model (instantie) uit de database
    protected string $pair;
    protected float $tradeSize;
    protected float $dropThreshold;      // Bijvoorbeeld 2 voor 2%
    protected float $profitThreshold;    // Bijvoorbeeld 3 voor 3%
    protected float $startBuy;
    protected float $budget;            // Het maximum budget voor open trades
    protected bool $accumulate;
    protected ?float $topEdge;
    protected ?float $stopLoss;
    
    // Voor event-driven aanpak:
    // referencePrice: De hoogste prijs (of startprijs) sinds de laatste koop, als basis voor het meten van een cumulatieve drop.
    protected ?float $referencePrice = null;
    // openTrades: Een array met alle open kooptransacties. Elke trade bevat buyPrice, volume en sold status.
    protected array $openTrades = [];
    // openTradeVolume: De som van alle open trade volumes.
    protected float $openTradeVolume = 0.0;
    // lastPrice: Laatst ontvangen prijs (voor logging).
    protected ?float $lastPrice = null;
    // Dry run mode: standaard ingeschakeld (true).
    protected bool $dryRun;

    public function __construct(
        KrakenApiServicePrivate $krakenApi,
        string $pair,
        float $tradeSize,
        float $dropThreshold,
        float $profitThreshold,
        float $startBuy,
        float $budget,
        bool $accumulate = false,
        ?float $topEdge = null,
        ?float $stopLoss = null,
        bool $dryRun = true
    ) {
        $this->krakenApi = $krakenApi;
        $this->pair = $pair;
        $this->tradeSize = $tradeSize;
        $this->dropThreshold = $dropThreshold;
        $this->profitThreshold = $profitThreshold;
        $this->startBuy = $startBuy;
        $this->budget = $budget;
        $this->accumulate = $accumulate;
        $this->topEdge = $topEdge;
        $this->stopLoss = $stopLoss;
        $this->dryRun = $dryRun;
    }

    // Zet de TradingBot model instantie, zodat je bijvoorbeeld de bot-ID kunt opslaan in transacties.
    public function setBot($bot)
    {
        $this->bot = $bot;
    }

   
    /**
     * Verwerkt een nieuwe prijsupdate. Deze methode wordt event-driven aangeroepen (bijvoorbeeld via Redis Pub/Sub).
     */
    public function processPriceUpdate(float $currentPrice): void
    {
        Log::info("Prijsupdate ontvangen voor {$this->pair}: €{$currentPrice}");

            // Controleer of de bot als 'stopped' is gemarkeerd in de database
    if ($this->bot && $this->bot->status === 'stopped') {
        Log::info("Bot status is 'stopped'. Stoppen met verwerken.");
        return;
    }
        $this->lastPrice = $currentPrice;

        // Update de referencePrice: als er een hogere prijs is, gebruik deze als basis.
        if (is_null($this->referencePrice) || $currentPrice > $this->referencePrice) {
            $this->referencePrice = $currentPrice;
            Log::info("Nieuwe hoogste prijs, referencePrice ingesteld op €{$currentPrice}");
        }

        // Eerst: Voor elke open trade, check of de winst (profit) target is bereikt.
        foreach ($this->openTrades as $key => $trade) {
            if (!$trade['sold']) {
                $profitGain = (($currentPrice - $trade['buyPrice']) / $trade['buyPrice']) * 100;
                Log::info("Profit gain voor trade met buyPrice €{$trade['buyPrice']}: " . round($profitGain, 2) . "%");
                if ($profitGain >= $this->profitThreshold) {
                    Log::info("Profit target bereikt voor trade met buyPrice €{$trade['buyPrice']}, verkoop wordt uitgevoerd.");
                    $this->placeSellOrder($trade, $currentPrice);
                    // Markeer de trade als verkocht en update het openTradeVolume
                    $this->openTrades[$key]['sold'] = true;
                    $this->openTradeVolume -= $trade['volume'];
                }
            }
        }

        // Vervolgens: Als er nog budget is voor een nieuwe koop, controleer de cumulatieve drop.
        if ($this->openTradeVolume + $this->tradeSize <= $this->budget) {
            $cumulativeDrop = (($this->referencePrice - $currentPrice) / $this->referencePrice) * 100;
            Log::info("Cumulatieve drop: " . round($cumulativeDrop, 2) . "% (ReferencePrice: €{$this->referencePrice}, Huidige prijs: €{$currentPrice})");
            if ($cumulativeDrop >= $this->dropThreshold) {
                Log::info("Drop threshold bereikt, koop wordt uitgevoerd.");
                $this->placeBuyOrder($this->tradeSize, $currentPrice);
                // Voeg de nieuwe koop toe aan openTrades
                $this->openTrades[] = [
                    'buyPrice' => $currentPrice,
                    'volume'   => $this->tradeSize,
                    'sold'     => false,
                ];
                $this->openTradeVolume += $this->tradeSize;
                // Reset de referencePrice naar de huidige prijs na de koop
                $this->referencePrice = $currentPrice;
            }
        } else {
            Log::info("Maximaal budget voor open trades bereikt. Geen nieuwe koop uitgevoerd.");
        }
    }

    /**
     * Registreer een kooporder.
     */
    public function placeBuyOrder(float $volume, float $price): void
    {
        if ($this->dryRun) {
            $tradeDetails = [
                'trading_bot_id' => $this->bot->id ?? null,
                'type'           => 'buy',
                'volume'         => $volume,
                'price'          => $price,
                'sold'           => false,
                'timestamp'      => now()->toDateTimeString(),
            ];
            \App\Models\BotTransaction::create($tradeDetails);
            Log::info("Dry run: buy order geregistreerd.", $tradeDetails);
            return;
        }
        try {
            $response = $this->krakenApi->sendRequest('/0/private/AddOrder', [
                'pair'      => $this->pair,
                'type'      => 'buy',
                'ordertype' => 'market',
                'volume'    => $volume,
            ]);
            Log::info("Buy order geplaatst:", $response);
        } catch (\Exception $e) {
            Log::error("Fout bij plaatsen buy order: " . $e->getMessage());
        }
    }

    /**
     * Registreer een verkooporder voor een specifieke open koop.
     * $trade is een array met onder andere 'buyPrice' en 'volume'.
     */
    public function placeSellOrder(array $trade, float $currentPrice): void
    {
        if ($this->dryRun) {
            // Zoek de corresponderende koop in de database en update deze
            $existingTrade = \App\Models\BotTransaction::where('trading_bot_id', $this->bot->id ?? null)
                                ->where('price', $trade['buyPrice'])
                                ->where('type', 'buy')
                                ->where('sold', false)
                                ->first();
            if ($existingTrade) {
                $sellVolume = $currentPrice * $trade['volume']; // Of een andere berekening
                $existingTrade->update([
                    'sold' => true,
                    'sell_volume' => $sellVolume,
                ]);
                Log::info("Dry run: sell order geregistreerd voor trade met buyPrice €{$trade['buyPrice']}.", [
                    'sell_volume' => $sellVolume,
                    'currentPrice' => $currentPrice
                ]);
            }
            return;
        }
        try {
            $response = $this->krakenApi->sendRequest('/0/private/AddOrder', [
                'pair'      => $this->pair,
                'type'      => 'sell',
                'ordertype' => 'market',
                'volume'    => $trade['volume'],
            ]);
            Log::info("Sell order geplaatst:", $response);
        } catch (\Exception $e) {
            Log::error("Fout bij plaatsen sell order: " . $e->getMessage());
        }
    }
}
