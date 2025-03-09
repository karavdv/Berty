<?php

namespace App\Jobs;

use App\Services\KrakenApiServicePrivate;
use App\Services\PriceUpdateService;
use Illuminate\Bus\Queueable;
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


        Log::info("📊 process priceupdate for bot-ID {$this->botId}");

        // Start processing price update
        $botService = new PriceUpdateService(new KrakenApiServicePrivate(), $this->botId);
        $botService->processPriceUpdate();
    }
}