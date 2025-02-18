<?php

namespace App\Services;

use App\Models\TradingBot;
use App\Models\BotRun;
use App\Models\BotTransaction;
use App\Services\KrakenApiServicePrivate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TradingBotService
{
    protected KrakenApiServicePrivate $krakenApi;
    protected int $botId;
    protected ?TradingBot $bot = null;
    protected ?BotRun $botRun = null;

    public function __construct(
        KrakenApiServicePrivate $krakenApi,
        int $botId
    ) {
        $this->krakenApi = $krakenApi;
        $this->botId = $botId;
        $this->bot = TradingBot::with('botRun')->find($this->botId);

        if (!$this->bot || !$this->bot->botRun) {
            Log::error("❌ Fout: Bot-ID {$this->botId} of bijhorende BotRun niet gevonden in database.");
            return;
        }

        // Haal botRun op uit de relatie
        $this->botRun = $this->bot->botRun;
    }


    /**
     * Verwerkt een nieuwe prijsupdate. Deze methode wordt event-driven aangeroepen .
     */
    public function processPriceUpdate(): void
    {
        if (!$this->botRun) {
            Log::error("⛔ BotRun ontbreekt, verwerking afgebroken.");
            return;
        }

        Log::info("Prijsupdate ontvangen in service voor {$this->bot->getPair()} {$this->bot->getBotId()}: €{$this->botRun->getLastPrice()}");

        // Controleer of de bot als 'stopped' is gemarkeerd in de database
        if ($this->bot->getStatus() === 'stopped') {
            Log::info("Bot status is 'stopped'. Stoppen met verwerken.");
            return;
        }

        // Update de referencePrice: als er een hogere prijs is, gebruik deze als nieuwe basis. Dit zorgt ervoor dat de daling berekend wordt vanaf de nieuwste hoogste prijs en niet vanaf de laatste koopprijs.
        if (is_null($this->botRun->getReferencePrice()) || $this->botRun->getLastPrice() > $this->botRun->getReferencePrice()) {
            $this->botRun->setReferencePrice($this->botRun->getLastPrice());
            Log::info("Nieuwe hoogste prijs, referencePrice ingesteld op €{$this->botRun->getLastPrice()}");
        }

        if (BotTransaction::where('trading_bot_id', $this->botId)->doesntExist()) {
            if ($this->bot->getStartBuy() <= $this->botRun->getLastPrice()) {
                Log::info("✅ The first buy has been reached.");
                $this->placeBuyOrder($this->bot->getTradeSize(), $this->botRun->getLastPrice());
            }
            return;
        }

        // Controleer open trades en verkoop indien nodig
        $this->checkAndProcessSellOrders();

        // Controleer of een nieuwe aankoop mogelijk is
        $this->checkAndProcessBuyOrder();
    }


    /**
     * Controleert of open trades verkocht moeten worden en plaatst verkooporders indien nodig.
     */
    private function checkAndProcessSellOrders(): void
    {
        $openTrades = BotTransaction::where('trading_bot_id', $this->botId)
            ->where('sold', false)
            ->get();

        if ($openTrades->isEmpty()) {
            Log::info("ℹ️ Geen open trades om te verwerken.");
            return;
        }

        foreach ($openTrades as $trade) {
            $profitGain = (($this->botRun->getLastPrice() - $trade->price) / $trade->price) * 100;

            Log::info("📊 Profit gain voor trade €{$trade->price}: " . round($profitGain, 2) . "%");

            if ($profitGain >= $this->bot->getProfitThreshold()) {
                Log::info("💰 Profit target bereikt! Verkoop wordt uitgevoerd.");
                $this->placeSellOrder($trade, $this->botRun->getLastPrice(), $profitGain);
            }
        }
    }


    /**
     * Controleert of een nieuwe aankoop moet worden gedaan en plaatst een buy order indien nodig.
     */
    private function checkAndProcessBuyOrder(): void
    {

        if (is_null($this->botRun->getReferencePrice()) || $this->botRun->getReferencePrice() == 0) {
            Log::warning("⚠️ Reference price is ongeldig. Koopcontrole afgebroken.");
            return;
        }

        $cumulativeDrop = (($this->botRun->getReferencePrice() - $this->botRun->getLastPrice()) / $this->botRun->getReferencePrice()) * 100;

        Log::info("📉 Cumulatieve drop: " . round($cumulativeDrop, 2) . "% (Ref: €{$this->botRun->getReferencePrice()}, Huidige: €{$this->botRun->getLastPrice()})");

        if (
            $cumulativeDrop >= $this->bot->getDropThreshold() &&
            $this->botRun->getOpenTradeVolume() + $this->bot->getTradeSize() <= $this->bot->getBudget()
        ) {
            /* //check if the distance to the top of the chart is bigger then the limit set bu the user, if it is set.
            if ($this->bot->getTopEdge() !== null && $this->bot->getTopEdge() !== 0){
                if ($this->bot->getTopEdge() > (($this->botRun->getTop() - $this->botRun->getLastPrice()) /$this->botRun->getTop())*100){
                    Log::info("⚠️ the current price is too close to the top, the order can not be placed. Current Top: {$this->botRun->getTop()}" );
                    return;
                }
            }*/

            Log::info("✅ Drop threshold bereikt, koop wordt uitgevoerd.");
            $this->placeBuyOrder($this->bot->getTradeSize(), $this->botRun->getLastPrice());

        }
    }

    /**
     * Registreer een kooporder en slaat deze op in de database.
     */
    public function placeBuyOrder(float $volume, float $price): void
    {
        // Update open trade volume and reset reference price
        $this->botRun->setOpenTradeVolume($this->botRun->getOpenTradeVolume() + $this->bot->getTradeSize());
        $this->botRun->setReferencePrice($this->botRun->getLastPrice());

        if ($this->bot->getDryRun()) {
            BotTransaction::create([
                'trading_bot_id' => $this->botId,
                'type' => 'buy',
                'volume' => $volume,
                'price' => $price,
                'sold' => false,
                'sell_amount' => null,
            ]);

            Log::info("🛒 Dry run: Buy order opgeslagen in database.");
            return;
        }
        /*try {
            $response = $this->krakenApi->sendRequest('/0/private/AddOrder', [
                'pair'      => $this->bot->getPair(),
                'type'      => 'buy',
                'ordertype' => 'market',
                'volume'    => $volume,
            ]);
            Log::info("Buy order geplaatst:", $response);
        } catch (\Exception $e) {
            Log::error("Fout bij plaatsen buy order: " . $e->getMessage());
        }*/
    }

    /**
     * Registreert een verkooporder en slaat deze op in de database.
     */
    public function placeSellOrder(BotTransaction $trade, float $currentPrice, float $profitGain): void
    {
        // Update open trade volume
        $this->botRun->setOpenTradeVolume($this->botRun->getOpenTradeVolume() - $this->bot->getTradeSize());

        if ($this->bot->getDryRun()) {
            $sellAmount = $trade->volume * (1+($profitGain/100));
            $trade->update([
                'sold' => true,
                'sell_amount' => $sellAmount,
                'sold_at' => now(),
            ]);

            $this->botRun->setProfit($sellAmount - $trade->volume);

            Log::info("💰 Dry run: Sell order opgeslagen in database.", [
                'sell_amount' => $sellAmount,
                'current_price' => $currentPrice
            ]);
            return;
        }
        /*try {
            $response = $this->krakenApi->sendRequest('/0/private/AddOrder', [
                'pair'      => $this->bot->getPair(),
                'type'      => 'sell',
                'ordertype' => 'market',
                'volume'    => $trade['volume'],
            ]);
            Log::info("Sell order geplaatst:", $response);
        } catch (\Exception $e) {
            Log::error("Fout bij plaatsen sell order: " . $e->getMessage());
        }*/
    }
}
