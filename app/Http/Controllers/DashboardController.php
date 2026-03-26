<?php

namespace App\Http\Controllers;

use App\Models\TradeHistory;
use App\Models\ErrorLog;
use App\Services\ExchangeService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected $exchange;
    protected $telegram;

    public function __construct()
    {
        $this->exchange = new ExchangeService();
        $this->telegram = new TelegramService();
    }

    /**
     * Halaman Utama Dashboard
     */
    public function index()
    {
        return view('dashboard.index');
    }

    /**
     * API: Statistik Utama (Ticker, Balance, P&L, Status Bot)
     */
    public function apiStats()
    {
        try {
            $symbol = config('trading.trading_pair', 'BTC/USDT');
            
            // 1. Ticker (Cache 5 detik)
            $ticker = Cache::remember('dash_ticker', 5, function () use ($symbol) {
                return $this->exchange->getTicker($symbol);
            });

            // 2. Saldo (Cache 30 detik)
            $balance = Cache::remember('dash_balance', 30, function () {
                return $this->exchange->getBalance();
            });

            // 3. P&L Hari Ini
            $todayProfit = TradeHistory::whereDate('created_at', now())
                ->where('side', 'sell')
                ->sum('profit_loss');

            // 4. Posisi Terbuka
            $openPosition = TradeHistory::where('symbol', $symbol)
                ->where('side', 'buy')
                ->whereNull('notes') // Belum di-close oleh sell
                ->latest()
                ->first();

            $unrealizedPnl = 0;
            if ($openPosition && isset($ticker['last'])) {
                $unrealizedPnl = ($ticker['last'] - $openPosition->price) * $openPosition->amount;
            }

            // 5. Status Bot (Berdasarkan Cache dari RunTradingBot)
            $lastRun = Cache::get('bot_last_run');
            $isBotActive = $lastRun && (now()->diffInSeconds($lastRun) < 90);

            return response()->json([
                'success' => true,
                'data' => [
                    'ticker' => [
                        'symbol' => $symbol,
                        'last' => $ticker['last'] ?? 0,
                        'change' => $ticker['percentage'] ?? 0,
                        'high' => $ticker['high'] ?? 0,
                        'low' => $ticker['low'] ?? 0,
                        'volume' => $ticker['quoteVolume'] ?? 0,
                    ],
                    'balance' => $balance,
                    'today_pnl' => round($todayProfit, 2),
                    'open_position' => $openPosition ? [
                        'amount' => $openPosition->amount,
                        'entry_price' => $openPosition->price,
                        'unrealized_pnl' => round($unrealizedPnl, 2),
                    ] : null,
                    'bot_status' => [
                        'active' => $isBotActive,
                        'last_run' => $lastRun ? $lastRun->diffForHumans() : 'Never',
                        'mode' => config('trading.sandbox_mode') ? 'TESTNET' : 'LIVE',
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Riwayat Trade (50 terbaru)
     */
    public function apiTrades()
    {
        $trades = TradeHistory::latest()->limit(50)->get();
        return response()->json(['success' => true, 'data' => $trades]);
    }

    /**
     * API: Error Log (20 terbaru)
     */
    public function apiErrors()
    {
        $errors = ErrorLog::latest()->limit(20)->get();
        return response()->json(['success' => true, 'data' => $errors]);
    }

    /**
     * API: Jalankan Bot secara Manual (1 siklus)
     */
    public function runBot()
    {
        try {
            // Jalankan artisan command secara internal
            \Illuminate\Support\Facades\Artisan::call('trading:run', ['--once' => true]);
            $output = \Illuminate\Support\Facades\Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'Bot berhasil dijalankan secara manual',
                'output'  => $output
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Cek Koneksi Telegram
     */
    public function checkTelegram()
    {
        try {
            $isActive = $this->telegram->testConnection();
            return response()->json([
                'success' => true,
                'active'  => $isActive,
                'message' => $isActive ? 'Telegram Aktif & Terhubung' : 'Gagal Menghubungi Telegram (Cek Token/Chat ID)'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'active' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Kirim Status ke Telegram (Manual Trigger)
     */
    public function sendTelegram()
    {
        try {
            $this->telegram->notifyGeneric("📢 *Status Check dari Dashboard*\nBot sedang dipantau melalui web interface.");
            return response()->json(['success' => true, 'message' => 'Notifikasi terkirim ke Telegram']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
