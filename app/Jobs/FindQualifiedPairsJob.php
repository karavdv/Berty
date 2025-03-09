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

        Log::info("Job FindQualifiedPairs started with parameters", [
            'currency' => $this->currency,
            'numberMovements' => $this->numberMovements,
            'changeRequired' => $this->changeRequired
        ]);

        $qualifiedPairs = iterator_to_array(
            $marketAnalyzer->findQualifiedPairs($this->currency, $this->numberMovements, $this->changeRequired)
        );

        // Saves the results in cache for 30 minutes
        Cache::put('qualified_pairs', $qualifiedPairs, now()->addMinutes(30));

        Log::info('Job FindQualifiedPairs completed', ['results' => count($qualifiedPairs)]);
    }
}
