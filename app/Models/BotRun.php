<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotRun extends Model
{
    use HasFactory;

    protected $table = 'bot_run';

    protected $fillable = [
        'bot_id',
        'last_price',
        'reference_price',
        'top',
        'open_trade_volume',
        'total_traded_volume',
        'last_trade_time',
        'profit',
        'is_live',
    ];

    protected $casts = [
        'last_price' => 'decimal:8',
        'reference_price' => 'decimal:8',
        'top' => 'decimal:8',
        'open_trade_volume' => 'decimal:8',
        'total_traded_volume' => 'decimal:8',
        'last_trade_time' => 'datetime',
        'profit' => 'decimal:8',
        'is_live' => 'boolean',
    ];

    // Relatie met de TradingBot
    public function tradingBot()
    {
        return $this->belongsTo(TradingBot::class, 'bot_id');
    }

    // GETTERS
    public function getLastPrice(): float
    {
        return (float) $this->last_price;
    }

    public function getReferencePrice(): float
    {
        return (float) $this->reference_price;
    }

    public function getTop(): ?float
    {
        return $this->top !== null ? (float) $this->top : null;
    }

    public function getOpenTradeVolume(): float
    {
        return (float) $this->open_trade_volume;
    }

    public function getTotalTradedVolume(): float
    {
        return (float) $this->total_traded_volume;
    }

    public function getLastTradeTime(): ?\Carbon\Carbon
    {
        return $this->last_trade_time;
    }

    public function getProfit(): float
    {
        return $this->profit ?? 0.0;
    }

    public function isLive(): bool
    {
        return (bool) $this->is_live;
    }

    // SETTERS
    public function setLastPrice(float $price): void
    {
        $this->last_price = $price;
        $this->save();
    }

    public function setReferencePrice(float $price): void
    {
        $this->reference_price = $price;
        $this->save();
    }

    public function setTop(?float $top): void
    {
        $this->top = $top;
        $this->save();
    }

    public function setOpenTradeVolume(float $volume): void
    {
        $this->open_trade_volume = $volume;
        $this->save();
    }

    public function setTotalTradedVolume(float $volume): void
    {
        $this->total_traded_volume = $volume;
        $this->save();
    }

    public function setLastTradeTime(\Carbon\Carbon $time): void
    {
        $this->last_trade_time = $time;
        $this->save();
    }

    public function setProfit($profit): void
    {
        $this->profit += $profit;
        $this->save();
    }

    public function setIsLive(bool $status): void
    {
        $this->is_live = $status;
        $this->save();
    }
}
