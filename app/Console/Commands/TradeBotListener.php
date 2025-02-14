<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Models\TradingBot;
use App\Services\TradingBotService;
use App\Services\KrakenApiServicePrivate;

class TradeBotListener extends Command
{
    protected $signature = 'tradebot:listen';
    protected $description = 'Luister naar prijsupdates via Redis en voer trading logica uit voor alle actieve bots';

    public function handle()
    {
        $this->info('TradeBotListener gestart. Luisteren naar prijsupdates voor alle actieve bots...');
        
        // Haal alle actieve bots op uit de database
        $bots = TradingBot::where('status', 'active')->get();
        if ($bots->isEmpty()) {
            $this->info("Geen actieve bots gevonden.");
            return;
        }
        
        // Maak een mapping van valutapaar naar TradingBotService-instantie
        $services = [];
        $krakenApi = app(KrakenApiServicePrivate::class);
        foreach ($bots as $botModel) {
            $this->info("Instantiëren bot ID: {$botModel->id} met pair: {$botModel->pair}");
            $service = new TradingBotService(
                $krakenApi,
                $botModel->pair,
                $botModel->trade_size,
                $botModel->drop_threshold,
                $botModel->profit_threshold,
                $botModel->start_buy,
                $botModel->max_buys,
                $botModel->accumulate,
                $botModel->top_edge,
                $botModel->stop_loss,
                $botModel->dry_run
            );
            $service->setBot($botModel);
            // We gaan ervan uit dat per bot het pair uniek is
            $services[$botModel->pair] = $service;
        }
        
        // Gebruik psubscribe om te luisteren naar alle Redis-kanalen die beginnen met "kraken_updates:"
        Redis::psubscribe(['kraken_updates:*'], function ($message, $channel) use ($services) {
            $data = json_decode($message, true);
            $price = $data['price'] ?? null;
            // Het kanaal heeft de structuur "kraken_updates:{pair}"
            $parts = explode(':', $channel);
            $pair = $parts[1] ?? null;
            if ($price && $pair && isset($services[$pair])) {
                $services[$pair]->processPriceUpdate($price);
                $this->info("Nieuwe prijs update voor {$pair}: {$price} verwerkt.");
            }
        });
    }
}
