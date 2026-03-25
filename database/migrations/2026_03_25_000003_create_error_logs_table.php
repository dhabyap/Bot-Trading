<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source');                         // Sumber error: 'ExchangeService', 'TradingStrategy', dll
            $table->string('error_type')->nullable();         // Tipe exception
            $table->text('message');                          // Pesan error
            $table->longText('stack_trace')->nullable();      // Stack trace lengkap
            $table->json('context')->nullable();              // Data konteks tambahan (payload, response, dll)
            $table->enum('severity', ['info', 'warning', 'error', 'critical'])->default('error');
            $table->boolean('telegram_notified')->default(false); // Sudah dikirim ke Telegram?
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['source', 'severity', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
