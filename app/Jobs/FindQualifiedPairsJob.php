<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\MarketAnalyzer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FindQualifiedPairsJob implements ShouldQueue
{
    use Queueable;

    private string $currency;
    private int $numberMovements;
    private float $changeRequired;

    /**
     * Create a new job instance.
     */
    public function __construct(string $currency, int $numberMovements, float $changeRequired)
    {
        $this->currency = $currency;
        $this->numberMovements = $numberMovements;
        $this->changeRequired = $changeRequired;
    }

    /**
     * Execute the job.
     */
    public function handle(MarketAnalyzer $marketAnalyzer): void
    {


        $marketAnalyzer->setup($this->currency, $this->numberMovements, $this->changeRequired);

        file_put_contents(storage_path('logs/custom.log'), "FindQualifiedPairsJob is gestart\n", FILE_APPEND);

        Log::info("Job gestart met parameters", [
            'currency' => $this->currency,
            'numberMovements' => $this->numberMovements,
            'changeRequired' => $this->changeRequired
        ]);
        Log::info('Job roept findQualifiedPairs() aan', ['currency' => $this->currency, 'numberMovements' => $this->numberMovements, 'changeRequired' => $this->changeRequired]);

        $qualifiedPairs = iterator_to_array(
            $marketAnalyzer->findQualifiedPairs($this->currency, $this->numberMovements, $this->changeRequired)
        );

        // Slaat de resultaten op in de cache voor 10 minuten
        Cache::put('qualified_pairs', $qualifiedPairs, now()->addMinutes(10));

        Log::info('Job voltooid', ['resultaten' => count($qualifiedPairs)]);
    }
}
