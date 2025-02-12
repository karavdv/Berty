<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingBot extends Model
{
    // Geef aan welke kolommen ingevuld mogen worden
    protected $fillable = [
        'pair',
        'trade_size',
        'drop_threshold',
        'profit_threshold',
        'start_buy',
        'max_buys',
        'accumulate',
        'top_edge',
        'stop_loss',
        'dry_run',
        'status'
    ];

    /**
     * Een trading bot kan meerdere transacties (buys/sells) hebben.
     */
    public function transactions()
    {
        return $this->hasMany(BotTransaction::class);
    }
}
