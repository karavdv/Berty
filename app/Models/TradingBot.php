<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingBot extends Model
{
    protected $fillable = [
        'pair',
        'trade_size',
        'drop_threshold',
        'profit_threshold',
        'start_buy',
        'budget',
        'accumulate',
        'top_edge',
        'bottom',
        'peak',
        'dry_run',
        'status',
        'last_price'
    ];

    // Automatic typecasting
    protected $casts = [
        'trade_size' => 'decimal:8',
        'drop_threshold' => 'decimal:2',
        'profit_threshold' => 'decimal:2',
        'start_buy' => 'decimal:8',
        'budget' => 'decimal:8',
        'accumulate' => 'boolean',
        'top_edge' => 'decimal:2',
        'bottom' => 'decimal:8',
        'peak' => 'decimal:8',
        'dry_run' => 'boolean',
        'last_price' => 'decimal:8',
    ];

    //Relation to BotTransaction.
    public function transactions()
    {
        return $this->hasMany(BotTransaction::class);
    }

    //Relation to BotRun.
    public function botRun()
    {
        return $this->hasOne(BotRun::class, 'bot_id');
    }

    // Getters
    public function getBotId(): int
    {
        return (int) $this->id;
    }

    public function getPair(): string
    {
        return $this->pair;
    }

    public function getTradeSize(): float
    {
        return (float) $this->trade_size;
    }

    public function getDropThreshold(): float
    {
        return (float) $this->drop_threshold;
    }
    
    public function getProfitThreshold(): float
    {
        return (float) $this->profit_threshold;
    }

    public function getStartBuy(): float
    {
        return (float) $this->start_buy;
    }

    public function getBudget(): float
    {
        return (float) $this->budget;
    }

    public function getAccumulate(): bool
    {
        return (bool) $this->accumulate;
    }

    public function getTopEdge(): ?float
    {
        return $this->top_edge !== null ? (float) $this->top_edge : null;
    }

    public function getBottom(): ?float
    {
        return $this->bottom !== null ? (float) $this->bottom : null;
    }

    public function getPeak(): ?float
    {
        return $this->peak !== null ? (float) $this->peak : null;
    }

    public function getDryRun(): bool
    {
        return (bool) $this->dry_run;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    // Setters
    public function setPair(string $pair): void
    {
        $this->pair = $pair;
        $this->save();
    }

    public function setTradeSize(float $tradeSize): void
    {
        $this->trade_size = $tradeSize;
        $this->save();
    }

    public function setDropThreshold(float $dropThreshold): void
    {
        $this->drop_threshold = $dropThreshold;
        $this->save();
    }

    public function setProfitThreshold(float $profitThreshold): void
    {
        $this->profit_threshold = $profitThreshold;
        $this->save();
    }

    public function setStartBuy(float $startBuy): void
    {
        $this->start_buy = $startBuy;
        $this->save();
    }

    public function setBudget(float $budget): void
    {
        $this->budget = $budget;
        $this->save();
    }

    public function setAccumulate(bool $accumulate): void
    {
        $this->accumulate = $accumulate;
        $this->save();
    }

    public function setTopEdge(?float $topEdge): void
    {
        $this->top_edge = $topEdge;
        $this->save();
    }

    public function setBottom(?float $bottom): void
    {
        $this->bottom = $bottom;
        $this->save();
    }

    public function setPeak(?float $peak): void
    {
        $this->peak = $peak;
        $this->save();
    }

    public function setDryRun(bool $dryRun): void
    {
        $this->dry_run = $dryRun;
        $this->save();
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->save();
    }
}
