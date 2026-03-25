<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class SendTestTelegram extends Command
{
    protected $signature   = 'trading:test-telegram';
    protected $description = 'Kirim pesan test ke Telegram Bot untuk verifikasi koneksi';

    public function handle(): int
    {
        $this->info('Mengirim pesan test ke Telegram...');

        $token  = config('telegram.bot_token');
        $chatId = config('telegram.chat_id');

        if (empty($token) || str_contains($token, 'GANTI')) {
            $this->error('TELEGRAM_BOT_TOKEN belum dikonfigurasi di .env!');
            $this->warn('Isi TELEGRAM_BOT_TOKEN dan TELEGRAM_CHAT_ID terlebih dahulu.');
            return Command::FAILURE;
        }

        if (empty($chatId) || str_contains($chatId, 'GANTI')) {
            $this->error('TELEGRAM_CHAT_ID belum dikonfigurasi di .env!');
            return Command::FAILURE;
        }

        $telegram = new TelegramService();
        $success  = $telegram->testConnection();

        if ($success) {
            $this->info('✅ Pesan test berhasil dikirim ke Telegram!');
            $this->info('Cek chat Telegram Anda untuk memastikan pesan diterima.');
            return Command::SUCCESS;
        }

        $this->error('❌ Gagal mengirim pesan ke Telegram.');
        $this->warn('Periksa: Bot token, Chat ID, dan koneksi internet.');
        return Command::FAILURE;
    }
}
