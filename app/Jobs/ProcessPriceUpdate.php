<?php

namespace App\Jobs;

use App\Models\TradingBot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPriceUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $botId;
    protected float $price;

    public function __construct(int $botId, float $price)
    {
        $this->botId = $botId;
        $this->price = $price;
    }

    public function handle()
    {
        $bot = TradingBot::find($this->botId);
        if (!$bot) {
            Log::warning("❌ Bot-ID {$this->botId} niet gevonden.");
            return;
        }

        Log::info("📊 Verwerk prijsupdate voor bot-ID {$bot->id} met prijs: {$this->price}");
        $bot->processPriceUpdate($this->price);
    }

    
/*
    protected string $pair;
    protected float $price;

    public function __construct(string $pair, float $price)
    {
        $this->pair = $pair;
        $this->price = $price;
    }

    public function handle()
    {
        // Haal alle actieve bots op die dit valutapaar volgen
        $bots = TradingBot::where('pair', $this->pair)
                          ->where('status', 'active')
                          ->get();

        if ($bots->isEmpty()) {
            Log::warning("⚠️ Geen actieve bots gevonden voor pair: {$this->pair}");
            return;
        }

        foreach ($bots as $bot) {
            Log::info("📊 Verwerk prijsupdate voor bot ID {$bot->id} met prijs: {$this->price}");
            $bot->processPriceUpdate($this->price);
        }
    }*/
}
