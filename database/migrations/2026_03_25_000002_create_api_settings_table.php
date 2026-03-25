<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_settings', function (Blueprint $table) {
            $table->id();
            $table->string('exchange_name');                 // Nama exchange, misal 'tokocrypto'
            $table->string('label')->nullable();             // Label deskriptif, misal 'Akun Utama'
            $table->text('api_key_encrypted');               // API Key (TERENKRIPSI via Laravel Crypt)
            $table->text('api_secret_encrypted');            // Secret Key (TERENKRIPSI)
            $table->boolean('is_active')->default(true);     // Hanya 1 yang aktif di saat bersamaan
            $table->boolean('sandbox_mode')->default(false); // false = live trading
            $table->json('permissions')->nullable();         // Izin yang diberikan (read, trade, dll)
            $table->timestamp('last_tested_at')->nullable(); // Kapan terakhir kali koneksi ditest
            $table->boolean('last_test_success')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_settings');
    }
};
