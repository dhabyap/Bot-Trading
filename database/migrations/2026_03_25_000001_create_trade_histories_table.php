<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_histories', function (Blueprint $table) {
            $table->id();
            $table->string('exchange')->default('tokocrypto');   // Nama exchange
            $table->string('symbol');                            // Contoh: BTC/USDT
            $table->enum('side', ['buy', 'sell']);               // Arah order
            $table->string('order_id')->nullable();              // ID order dari exchange
            $table->enum('order_type', ['market', 'limit'])->default('market');
            $table->decimal('amount', 20, 8);                    // Jumlah koin
            $table->decimal('price', 20, 8)->nullable();         // Harga eksekusi
            $table->decimal('cost', 20, 8)->nullable();          // Total biaya (amount * price)
            $table->decimal('fee', 20, 8)->nullable();           // Biaya transaksi
            $table->string('fee_currency')->nullable();          // Mata uang biaya
            $table->string('strategy')->nullable();              // Nama strategi yang memicu
            $table->json('signal_data')->nullable();             // Data sinyal mentah (RSI, EMA, dll)
            $table->enum('status', ['open', 'filled', 'cancelled', 'error'])->default('open');
            $table->decimal('profit_loss', 20, 8)->nullable();   // P&L jika posisi closed
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['symbol', 'side', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_histories');
    }
};
