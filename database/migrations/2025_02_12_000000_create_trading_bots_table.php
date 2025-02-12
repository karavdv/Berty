<?php

// database/migrations/2025_02_12_000000_create_trading_bots_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTradingBotsTable extends Migration
{
    public function up()
    {
        Schema::create('trading_bots', function (Blueprint $table) {
            $table->id();
            $table->string('pair');
            $table->decimal('trade_size', 15, 8);
            $table->decimal('drop_threshold', 5, 2);
            $table->decimal('profit_threshold', 5, 2);
            $table->decimal('start_buy', 15, 8);
            $table->decimal('max_buys', 15, 8);
            $table->boolean('accumulate')->default(false);
            $table->decimal('top_edge', 5, 2)->nullable();
            $table->decimal('stop_loss', 5, 2)->nullable();
            $table->boolean('dry_run')->default(true);
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('trading_bots');
    }
}
