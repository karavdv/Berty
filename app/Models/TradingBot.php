<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class TradingBot extends Model
{
    // Geef aan welke kolommen ingevuld mogen worden
    protected $fillable = [
        'pair',
        'trade_size',
        'drop_threshold',
        'profit_threshold',
        'start_buy',
        'budget',
        'accumulate',
        'top_edge',
        'stop_loss',
        'dry_run',
        'status',
        'last_price'
    ];

    // Zorgt voor automatische typecasting
    protected $casts = [
        'trade_size' => 'decimal:8',
        'drop_threshold' => 'decimal:2',
        'profit_threshold' => 'decimal:2',
        'start_buy' => 'decimal:8',
        'budget' => 'decimal:8',
        'accumulate' => 'boolean',
        'top_edge' => 'decimal:2',
        'stop_loss' => 'decimal:2',
        'dry_run' => 'boolean',
        'last_price' => 'decimal:8',
    ];

    /**
     * Een trading bot kan meerdere transacties (buys/sells) hebben.
     */
    public function transactions()
    {
        return $this->hasMany(BotTransaction::class);
    }

    public function botRun()
    {
        return $this->hasOne(BotRun::class, 'bot_id');
    }

    public function getBotId(): int
    {
        return (int) $this->id;
    }

    public function getPair(): string
    {
        return $this->pair;
    }

    public function setPair(string $pair): void
    {
        $this->pair = $pair;
        $this->save();
    }

    public function getTradeSize(): float
    {
        return (float) $this->trade_size;
    }

    public function setTradeSize(float $tradeSize): void
    {
        $this->trade_size = $tradeSize;
        $this->save();
    }

    public function getDropThreshold(): float
    {
        return (float) $this->drop_threshold;
    }

    public function setDropThreshold(float $dropThreshold): void
    {
        $this->drop_threshold = $dropThreshold;
        $this->save();
    }

    public function getProfitThreshold(): float
    {
        return (float) $this->profit_threshold;
    }

    public function setProfitThreshold(float $profitThreshold): void
    {
        $this->profit_threshold = $profitThreshold;
        $this->save();
    }

    public function getStartBuy(): float
    {
        return (float) $this->start_buy;
    }

    public function setStartBuy(float $startBuy): void
    {
        $this->start_buy = $startBuy;
        $this->save();
    }

    public function getBudget(): float
    {
        return (float) $this->budget;
    }

    public function setBudget(float $budget): void
    {
        $this->budget = $budget;
        $this->save();
    }

    public function getAccumulate(): bool
    {
        return (bool) $this->accumulate;
    }

    public function setAccumulate(bool $accumulate): void
    {
        $this->accumulate = $accumulate;
        $this->save();
    }

    public function getTopEdge(): ?float
    {
        return $this->top_edge !== null ? (float) $this->top_edge : null;
    }

    public function setTopEdge(?float $topEdge): void
    {
        $this->top_edge = $topEdge;
        $this->save();
    }

    public function getStopLoss(): ?float
    {
        return $this->stop_loss !== null ? (float) $this->stop_loss : null;
    }

    public function setStopLoss(?float $stopLoss): void
    {
        $this->stop_loss = $stopLoss;
        $this->save();
    }

    public function getDryRun(): bool
    {
        return (bool) $this->dry_run;
    }

    public function setDryRun(bool $dryRun): void
    {
        $this->dry_run = $dryRun;
        $this->save();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->save();
    }

    
}
