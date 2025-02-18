<?php

namespace App\Jobs;

use App\Models\TradingBot;
use App\Services\KrakenApiServicePrivate;
use App\Services\TradingBotService;
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

    public function __construct(int $botId)
    {
        $this->botId = $botId;
    }

    public function handle()
    {


        Log::info("📊 Verwerk prijsupdate voor bot-ID {$this->botId}");

        // Start de bot service
        $botService = new TradingBotService(new KrakenApiServicePrivate(), $this->botId);
        $botService->processPriceUpdate();
    }
}