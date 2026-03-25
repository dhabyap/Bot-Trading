<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ErrorLog;

class TelegramService
{
    protected string $token;
    protected string $chatId;
    protected string $apiUrl;

    public function __construct()
    {
        $this->token  = config('telegram.bot_token', '');
        $this->chatId = config('telegram.chat_id', '');
        $this->apiUrl = config('telegram.api_url', 'https://api.telegram.org/bot');
    }

    /**
     * Kirim pesan teks biasa ke Telegram
     */
    public function send(string $message, ?string $chatId = null): bool
    {
        if (empty($this->token)) {
            Log::warning('[TelegramService] Bot token belum dikonfigurasi. Pesan tidak terkirim.');
            return false;
        }

        $targetChatId = $chatId ?? $this->chatId;

        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}{$this->token}/sendMessage", [
                'chat_id'    => $targetChatId,
                'text'       => $message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful() && $response->json('ok')) {
                Log::info('[TelegramService] Pesan terkirim: ' . substr($message, 0, 80) . '...');
                return true;
            }

            Log::warning('[TelegramService] Gagal kirim: ' . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error('[TelegramService] Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifikasi: Sinyal BUY terdeteksi & order berhasil
     */
    public function notifyBuy(string $symbol, float $price, float $amount, float $cost, string $orderId, string $strategy): bool
    {
        $message = implode("\n", [
            "🟢 <b>ORDER BUY DIEKSEKUSI</b>",
            "━━━━━━━━━━━━━━━━━━━━━",
            "📌 Pasangan  : <b>{$symbol}</b>",
            "💰 Harga     : <b>$" . number_format($price, 2) . "</b>",
            "🔢 Jumlah    : <b>" . number_format($amount, 8) . "</b>",
            "💵 Total     : <b>$" . number_format($cost, 2) . " USDT</b>",
            "📊 Strategi  : <b>{$strategy}</b>",
            "🆔 Order ID  : <code>{$orderId}</code>",
            "⏰ Waktu     : <b>" . now()->format('Y-m-d H:i:s') . " WIB</b>",
        ]);

        return $this->send($message);
    }

    /**
     * Notifikasi: Sinyal SELL terdeteksi & order berhasil
     */
    public function notifySell(string $symbol, float $price, float $amount, float $cost, string $orderId, string $strategy, ?float $profitLoss = null): bool
    {
        $plText = '';
        if ($profitLoss !== null) {
            $plEmoji = $profitLoss >= 0 ? '📈' : '📉';
            $plSign  = $profitLoss >= 0 ? '+' : '';
            $plText  = "\n{$plEmoji} P&L       : <b>{$plSign}" . number_format($profitLoss, 2) . " USDT</b>";
        }

        $message = implode("\n", [
            "🔴 <b>ORDER SELL DIEKSEKUSI</b>",
            "━━━━━━━━━━━━━━━━━━━━━",
            "📌 Pasangan  : <b>{$symbol}</b>",
            "💰 Harga     : <b>$" . number_format($price, 2) . "</b>",
            "🔢 Jumlah    : <b>" . number_format($amount, 8) . "</b>",
            "💵 Total     : <b>$" . number_format($cost, 2) . " USDT</b>" . $plText,
            "📊 Strategi  : <b>{$strategy}</b>",
            "🆔 Order ID  : <code>{$orderId}</code>",
            "⏰ Waktu     : <b>" . now()->format('Y-m-d H:i:s') . " WIB</b>",
        ]);

        return $this->send($message);
    }

    /**
     * Notifikasi: Error sistem
     */
    public function notifyError(string $source, string $errorMessage, string $severity = 'error'): bool
    {
        $emoji = match($severity) {
            'critical' => '🆘',
            'error'    => '❌',
            'warning'  => '⚠️',
            default    => 'ℹ️',
        };

        $message = implode("\n", [
            "{$emoji} <b>BOT ERROR — " . strtoupper($severity) . "</b>",
            "━━━━━━━━━━━━━━━━━━━━━",
            "📍 Sumber    : <code>{$source}</code>",
            "📝 Pesan     : <b>" . htmlspecialchars(substr($errorMessage, 0, 400)) . "</b>",
            "⏰ Waktu     : <b>" . now()->format('Y-m-d H:i:s') . " WIB</b>",
            "",
            "🔧 <i>Silakan cek log sistem untuk detail lengkap.</i>",
        ]);

        return $this->send($message);
    }

    /**
     * Notifikasi: Bot mulai berjalan
     */
    public function notifyBotStart(string $symbol, string $strategy, bool $sandboxMode): bool
    {
        $mode = $sandboxMode ? '🧪 SANDBOX' : '🔴 LIVE TRADING';

        $message = implode("\n", [
            "🤖 <b>BOT TRADING AKTIF</b>",
            "━━━━━━━━━━━━━━━━━━━━━",
            "📌 Pasangan  : <b>{$symbol}</b>",
            "📊 Strategi  : <b>{$strategy}</b>",
            "⚙️  Mode      : <b>{$mode}</b>",
            "⏰ Mulai     : <b>" . now()->format('Y-m-d H:i:s') . " WIB</b>",
        ]);

        return $this->send($message);
    }

    /**
     * Notifikasi: Ringkasan harian
     */
    public function notifyDailySummary(int $totalBuy, int $totalSell, float $totalPL, float $currentBalance): bool
    {
        $plEmoji = $totalPL >= 0 ? '📈' : '📉';
        $plSign  = $totalPL >= 0 ? '+' : '';

        $message = implode("\n", [
            "📊 <b>RINGKASAN HARIAN BOT TRADING</b>",
            "━━━━━━━━━━━━━━━━━━━━━",
            "🟢 Order Buy    : <b>{$totalBuy} kali</b>",
            "🔴 Order Sell   : <b>{$totalSell} kali</b>",
            "{$plEmoji} Total P&amp;L   : <b>{$plSign}" . number_format($totalPL, 2) . " USDT</b>",
            "💰 Saldo USDT  : <b>" . number_format($currentBalance, 2) . " USDT</b>",
            "📅 Tanggal     : <b>" . now()->format('Y-m-d') . "</b>",
        ]);

        return $this->send($message);
    }

    /**
     * Test koneksi Telegram
     */
    public function testConnection(): bool
    {
        $message = "✅ <b>Test Koneksi Berhasil!</b>\n\nBot Trading Laravel berhasil terhubung ke Telegram.\n⏰ " . now()->format('Y-m-d H:i:s');
        return $this->send($message);
    }
}
