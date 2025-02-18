<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bot_run', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bot_id')->index();
            $table->decimal('last_price', 15, 8);
            $table->decimal('reference_price', 15, 8);
            $table->decimal('top', 15, 8)->nullable();
            $table->decimal('open_trade_volume', 15, 8);
            $table->decimal('total_traded_volume', 15, 8)->default(0);
            $table->timestamp('last_trade_time')->nullable();
            $table->decimal('profit', 15, 8)->nullable();
            $table->boolean('is_live');
            $table->timestamps();

            $table->foreign('bot_id')->references('id')->on('trading_bots')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_run');
    }
};
