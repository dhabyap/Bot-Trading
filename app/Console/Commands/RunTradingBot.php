<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use App\Services\ExchangeService;
use App\Services\TradingStrategy;
use App\Models\TradeHistory;
use App\Models\ErrorLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunTradingBot extends Command
{
    protected $signature   = 'trading:run {--once : Jalankan sekali saja tanpa loop}';
    protected $description = 'Jalankan bot trading (dipanggil oleh Laravel Scheduler setiap menit)';

    protected ExchangeService $exchange;
    protected TelegramService $telegram;
    protected TradingStrategy $strategy;

    public function handle(): int
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $this->info("[{$timestamp}] ▶ Bot Trading berjalan...");
        Log::info("[RunTradingBot] Siklus dimulai pada {$timestamp}");

        try {
            $this->exchange = new ExchangeService();
            $this->telegram = new TelegramService();
            $this->strategy = new TradingStrategy();

            $symbol    = config('trading.trading_pair', 'BTC/USDT');
            $timeframe = config('trading.ohlcv_timeframe', '1h');
            $limit     = config('trading.ohlcv_limit', 100);

            // ── 1. AMBIL DATA OHLCV ──────────────────────────────
            $this->info("  Mengambil data OHLCV {$symbol} ({$timeframe})...");
            $ohlcv = $this->exchange->getOHLCV($symbol, $timeframe, $limit);

            // ── 2. ANALISIS SINYAL ───────────────────────────────
            $this->info("  Menganalisis sinyal...");
            $result     = $this->strategy->analyze($ohlcv);
            $signal     = $result['signal'];     // 'buy' | 'sell' | 'hold'
            $reason     = $result['reason'];
            $indicators = $result['indicators'];

            $this->info("  Sinyal: " . strtoupper($signal) . " | {$reason}");
            Log::info("[RunTradingBot] Sinyal={$signal} | {$reason}");

            // ── 3. EKSEKUSI ORDER ────────────────────────────────
            if ($signal === 'buy') {
                $this->executeBuy($symbol, $indicators);
            } elseif ($signal === 'sell') {
                $this->executeSell($symbol, $indicators);
            } else {
                $this->info("  Tidak ada aksi. HOLD.");
            }

        } catch (\Exception $e) {
            $this->handleError('RunTradingBot', $e);
            return Command::FAILURE;
        }

        $this->info("[" . now()->format('H:i:s') . "] ✓ Siklus selesai.");
        return Command::SUCCESS;
    }

    // ──────────────────────────────────────────────────────────────
    // EKSEKUSI ORDER BUY
    // ──────────────────────────────────────────────────────────────
    protected function executeBuy(string $symbol, array $indicators): void
    {
        $amountUsdt = (float) config('trading.amount_usdt', 5.0);

        $this->info("  🟢 BUY signal! Mengeksekusi order {$amountUsdt} USDT...");

        $order = $this->exchange->createBuyOrder($amountUsdt, $symbol);

        $price   = $order['average']  ?? $order['price']  ?? ($indicators['ema9'] ?? 0);
        $filled  = $order['filled']   ?? $order['amount'] ?? 0;
        $cost    = $order['cost']     ?? ($price * $filled);
        $fee     = $order['fee']['cost']     ?? 0;
        $feeCur  = $order['fee']['currency'] ?? 'USDT';
        $orderId = $order['id'] ?? 'unknown';

        // Simpan ke database
        TradeHistory::create([
            'exchange'    => config('trading.exchange_name'),
            'symbol'      => $symbol,
            'side'        => 'buy',
            'order_id'    => $orderId,
            'order_type'  => 'market',
            'amount'      => $filled,
            'price'       => $price,
            'cost'        => $cost,
            'fee'         => $fee,
            'fee_currency'=> $feeCur,
            'strategy'    => 'RSI_EMA_Crossover',
            'signal_data' => $indicators,
            'status'      => 'filled',
        ]);

        // Notifikasi Telegram
        $this->telegram->notifyBuy($symbol, $price, $filled, $cost, $orderId, 'RSI_EMA_Crossover');
        $this->info("  ✅ BUY order berhasil! ID: {$orderId}");
    }

    // ──────────────────────────────────────────────────────────────
    // EKSEKUSI ORDER SELL
    // ──────────────────────────────────────────────────────────────
    protected function executeSell(string $symbol, array $indicators): void
    {
        $this->info("  🔴 SELL signal! Mencari posisi buy terakhir...");

        // Ambil posisi buy terakhir yang belum di-sell
        $lastBuy = TradeHistory::where('symbol', $symbol)
                               ->where('side', 'buy')
                               ->where('status', 'filled')
                               ->latest()
                               ->first();

        if (!$lastBuy) {
            $this->warn("  ⚠ Tidak ada posisi buy terbuka. SELL diabaikan.");
            return;
        }

        $amountToSell = $lastBuy->amount;
        $this->info("  Menjual {$amountToSell} {$symbol}...");

        $order = $this->exchange->createSellOrder($amountToSell, $symbol);

        $price     = $order['average']  ?? $order['price']  ?? ($indicators['ema9'] ?? 0);
        $filled    = $order['filled']   ?? $order['amount'] ?? 0;
        $cost      = $order['cost']     ?? ($price * $filled);
        $fee       = $order['fee']['cost']     ?? 0;
        $feeCur    = $order['fee']['currency'] ?? 'USDT';
        $orderId   = $order['id'] ?? 'unknown';

        // Hitung P&L
        $buyTotal   = $lastBuy->cost ?? 0;
        $sellTotal  = $cost;
        $profitLoss = $sellTotal - $buyTotal - $fee;

        // Simpan ke database
        TradeHistory::create([
            'exchange'    => config('trading.exchange_name'),
            'symbol'      => $symbol,
            'side'        => 'sell',
            'order_id'    => $orderId,
            'order_type'  => 'market',
            'amount'      => $filled,
            'price'       => $price,
            'cost'        => $cost,
            'fee'         => $fee,
            'fee_currency'=> $feeCur,
            'strategy'    => 'RSI_EMA_Crossover',
            'signal_data' => $indicators,
            'status'      => 'filled',
            'profit_loss' => $profitLoss,
        ]);

        // Update status buy order menjadi closed (gunakan notes)
        $lastBuy->update(['notes' => 'Closed by sell order ' . $orderId]);

        // Notifikasi Telegram
        $this->telegram->notifySell($symbol, $price, $filled, $cost, $orderId, 'RSI_EMA_Crossover', $profitLoss);
        $this->info("  ✅ SELL selesai! P&L: " . ($profitLoss >= 0 ? '+' : '') . number_format($profitLoss, 2) . " USDT");
    }

    // ──────────────────────────────────────────────────────────────
    // PENANGANAN ERROR
    // ──────────────────────────────────────────────────────────────
    protected function handleError(string $source, \Throwable $e): void
    {
        $this->error("❌ Error di {$source}: " . $e->getMessage());
        Log::error("[{$source}] " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

        // Catat ke database
        try {
            $log = ErrorLog::log($source, $e->getMessage(), 'error', null, $e);

            // Kirim ke Telegram
            $telegram = new TelegramService();
            $sent = $telegram->notifyError($source, $e->getMessage(), 'error');

            if ($sent) {
                $log->update(['telegram_notified' => true]);
            }
        } catch (\Exception $dbErr) {
            Log::critical('Gagal mencatat error ke DB: ' . $dbErr->getMessage());
        }
    }
}
