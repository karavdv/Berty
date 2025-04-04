<?php

namespace App\Services;

use App\Models\TradingBot;
use App\Models\BotRun;
use App\Models\BotTransaction;
use App\Services\KrakenApiServicePrivate;
use Illuminate\Support\Facades\Log;

class PriceUpdateService
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
            Log::error("❌ Error: Bot-ID {$this->botId} or associated BotRun not found in database.");
            return;
        }

        // Retrieve botRun through relation
        $this->botRun = $this->bot->botRun;
    }


    /**
     * Process new price update. The function is event-driven.
     */
    public function processPriceUpdate(): void
    {
        if (!$this->botRun) {
            Log::error("⛔ No BotRun found, processing aborted.");
            return;
        }

        Log::info("Priceupdate received in service for {$this->bot->getPair()} {$this->bot->getBotId()}: €{$this->botRun->getLastPrice()}");

        // Check bot status
        if ($this->bot->getStatus() === 'stopped') {
            Log::info("Bot status is 'stopped'. Processing aborted.");
            return;
        }

        // Update referencePrice: if there is a higher price, use it as the new baseline. 
        // This ensures that the drop is calculated from the latest highest price, rather than from the last purchase price.
        if (is_null($this->botRun->getReferencePrice()) || $this->botRun->getLastPrice() > $this->botRun->getReferencePrice()) {
            $this->botRun->setReferencePrice($this->botRun->getLastPrice());
            Log::info("New reference price, referencePrice set to €{$this->botRun->getLastPrice()}");
        }
        //For a new bot; the first buy happens when the price set by the user is reached
        if (BotTransaction::where('trading_bot_id', $this->botId)->doesntExist()) {
            if (abs(floatval($this->bot->getStartBuy()) - floatval($this->botRun->getLastPrice())) <= floatval($this->botRun->getLastPrice()) * 0.005) {
                Log::info("✅ The first buy has been reached.");
                $this->placeBuyOrder($this->bot->getTradeSize(), $this->botRun->getLastPrice());
            } else {
            Log::info("⛔ The first buy has not been reached.");
            return;
            }
        }

        // Start check for sell conditions
        $this->checkAndProcessSellOrders();

        // Start check for buy conditions
        $this->checkAndProcessBuyOrder();
    }


    // Check open trades en sell when profitmargin has been reached

    private function checkAndProcessSellOrders(): void
    {
        $openTrades = BotTransaction::where('trading_bot_id', $this->botId)
            ->where('sold', false)
            ->get();

        if ($openTrades->isEmpty()) {
            Log::info("ℹ️ No open trades to process.");
            return;
        }

        foreach ($openTrades as $trade) {
            $profitGain = (($this->botRun->getLastPrice() - $trade->price) / $trade->price) * 100;

            Log::info("📊 Profit gain for trade €{$trade->price}: " . round($profitGain, 2) . "%");

            if ($profitGain >= $this->bot->getProfitThreshold()) {
                Log::info("💰 Profit target reached! Sell is being executed.");
                $this->placeSellOrder($trade, $this->botRun->getLastPrice(), $profitGain);
            }
        }
    }


    // Check available budget and whether dropmargin has been reached. If so, make a buy
    private function checkAndProcessBuyOrder(): void
    {
        if (is_null($this->botRun->getReferencePrice()) || $this->botRun->getReferencePrice() == 0) {
            Log::warning("⚠️ Reference price is invalid. Price update processing is terminated.");
            return;
        }

        $cumulativeDrop = (($this->botRun->getReferencePrice() - $this->botRun->getLastPrice()) / $this->botRun->getReferencePrice()) * 100;

        Log::info("📉 Cumulative drop: " . round($cumulativeDrop, 2) . "% (Ref: €{$this->botRun->getReferencePrice()}, Current: €{$this->botRun->getLastPrice()})");

        if ( $cumulativeDrop >= $this->bot->getDropThreshold() &&
            $this->botRun->getOpenTradeVolume() + $this->bot->getTradeSize() <= $this->botRun->getWorkbudget()) {
            //check if the distance to the top of the chart(last 7 days) is bigger then the limit set by the user, if it is set.
            if ($this->bot->getTopEdge() !== null && $this->bot->getTopEdge() !== 0){
                if ($this->botRun->getTop() === null || $this->botRun->getTop() == 0) {
                    Log::warning("⚠️ Invalid top value. Buy order cannot be placed.");
                    return;
                }
                if ($this->bot->getTopEdge() > (($this->botRun->getTop() - $this->botRun->getLastPrice()) /$this->botRun->getTop())*100){
                    Log::info("⚠️ the current price is too close to the top, the order can not be placed. Current Top: {$this->botRun->getTop()}" );
                    return;
                }
            }

            Log::info("✅ Drop threshold reached, Buy is being executed.");
            $this->placeBuyOrder($this->bot->getTradeSize(), $this->botRun->getLastPrice());
        }
    }

    //places the buy order and creates a new bot transaction in the database
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

            Log::info("🛒 Dry run: Buy order is saved in database.");
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

    //places the sell order and updates the associated bot transaction in the database
    public function placeSellOrder(BotTransaction $trade, float $currentPrice, float $profitGain): void
    {
        // Update open trade volume
        $this->botRun->setOpenTradeVolume($this->botRun->getOpenTradeVolume() - $this->bot->getTradeSize());

        if ($this->bot->getDryRun()) {
            $sellAmount = ($trade->volume * (1 + ($profitGain / 100))) * 0.9975; // 0.9975 is to take in account the 0.25% fee percentage on Kraken on limit sell orders
            $trade->update([
                'sold' => true,
                'sell_amount' => $sellAmount,
                'sold_at' => now(),
            ]);
            $profit = $sellAmount - ($trade->volume * 1.0025); // 1.0025 is to take in account the 0.25% fee percentage on Kraken on limit buy orders
            $this->botRun->setProfit($profit);
            //If accumulate is set, the profit is added to the workbudget
            if ($this->bot->getAccumulate()) {
                $this->botRun->setWorkbudget($profit);
            }

            Log::info("💰 Dry run: Sell order saved in database.", [
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
