<?php
// database/migrations/2025_01_01_000001_create_bot_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('bot_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trading_bot_id');
            // In dit model vertegenwoordigt elk record een aankoop (buy)
            $table->enum('type', ['buy']); // We slaan enkel buys op; sells worden in dezelfde record vastgelegd
            $table->decimal('volume', 15, 8);
            $table->decimal('price', 15, 8);
            //kolommen voor sell-informatie
            $table->boolean('sold')->default(false);  // Wordt op true gezet zodra de sell is uitgevoerd
            $table->decimal('sell_amount', 15, 8)->nullable(); // Het bedrag van de verkoop, null als er nog geen sell is
            $table->timestamp('sold_at')->nullable(); // Specifieke timestamp voor verkoop
            $table->timestamps();

            $table->foreign('trading_bot_id')->references('id')->on('trading_bots')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bot_transactions');
    }
}
