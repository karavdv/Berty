<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotTransaction extends Model
{
    protected $fillable = [
        'trading_bot_id',
        'type',
        'volume',
        'price',
        'sold',
        'sell_amount',
        'sold_at'
    ];

    // Automatic typecasting
    protected $casts = [
        'sold_at' => 'datetime',
    ];

    // Relation to TradingBot
    public function tradingBot()
    {
        return $this->belongsTo(TradingBot::class);
    }
}
