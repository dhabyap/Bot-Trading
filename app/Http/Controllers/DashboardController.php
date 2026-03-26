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
            $startTicker = microtime(true);
            $ticker = Cache::remember('dash_ticker', 5, function () use ($symbol) {
                return $this->exchange->getTicker($symbol);
            });
            $binanceLatency = round((microtime(true) - $startTicker) * 1000);

            // 2. Saldo (Cache 30 detik)
            $balance = Cache::remember('dash_balance', 30, function () {
                $rawBalance = $this->exchange->getBalance();
                
                // Watchlist aset utama
                $watchlist = ['BTC', 'ETH', 'BNB', 'USDT', 'BUSD', 'FDUSD'];
                
                // Filter: Hanya yang saldo > 0.0001 ATAU ada di watchlist
                return array_filter($rawBalance, function($val, $asset) use ($watchlist) {
                    return $val > 0.0001 || in_array($asset, $watchlist);
                }, ARRAY_FILTER_USE_BOTH);
            });

            // Estimasi Total USDT (Sederhana: USDT + (BTC * Price))
            $totalUsdt = $balance['USDT'] ?? 0;
            if (isset($balance['BTC']) && isset($ticker['last'])) {
                $totalUsdt += ($balance['BTC'] * $ticker['last']);
            }

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

            // 4. Hitung Performance Metrics
            $allSells        = TradeHistory::where('symbol', $symbol)->where('side', 'sell')->get();
            $dailySells      = TradeHistory::where('symbol', $symbol)->where('side', 'sell')
                                           ->whereDate('created_at', now()->toDateString())->get();
            
            $totalPnl        = $allSells->sum('profit_loss');
            $dailyPnl        = $dailySells->sum('profit_loss');
            
            $wins            = $allSells->where('profit_loss', '>', 0)->count();
            $winRate         = $allSells->count() > 0 ? round(($wins / $allSells->count()) * 100, 2) : 0;
            
            // Simpel Max Drawdown (dari history koin trading saat ini)
            $maxDrawdown     = 0; // Logic riil butuh data equity curve over time
            
            // 5. Pending Orders
            $pendingOrders   = $this->exchange->getOpenOrders($symbol);

            return response()->json([
                'success' => true,
                'data'    => [
                    'ticker'     => [
                        'symbol' => $symbol,
                        'last'   => $ticker['last']   ?? 0,
                        'change' => $ticker['percentage'] ?? 0,
                        'high'   => $ticker['high']   ?? 0,
                        'low'    => $ticker['low']    ?? 0,
                        'volume' => $ticker['quoteVolume'] ?? 0,
                    ],
                    'balance'       => $balance,
                    'total_usdt'    => round($totalUsdt, 2),
                    'performance'   => [
                        'daily_pnl'    => round($dailyPnl, 2),
                        'total_pnl'    => round($totalPnl, 2),
                        'win_rate'     => $winRate,
                        'max_drawdown' => $maxDrawdown,
                    ],
                    'open_position' => $openPosition ? [
                        'amount' => $openPosition->amount,
                        'entry_price' => $openPosition->price,
                        'unrealized_pnl' => round($unrealizedPnl, 2),
                    ] : null,
                    'pending_orders'=> array_slice($pendingOrders, 0, 5), // Ambil 5 terakhir
                    'strategy'      => [
                        'name' => 'RSI + EMA Crossover',
                        'tp'   => 2.0,
                        'sl'   => 1.5,
                    ],
                    'binance_latency' => $binanceLatency,
                    'bot_status' => [
                        'active'   => $isBotActive,
                        'last_run' => $lastRun ? $lastRun->diffForHumans() : 'Never',
                        'mode'     => config('trading.sandbox_mode') ? 'TESTNET' : 'LIVE',
                    ]
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
     * API: Kill Switch — Berhenti total & Jual Posisi
     */
    public function killSwitch()
    {
        try {
            $symbol = config('trading.trading_pair', 'BTC/USDT');
            
            // 1. Batalkan semua order terbuka
            $this->exchange->cancelAllOrders($symbol);
            
            // 2. Tutup semua posisi (Market Sell)
            $result = $this->exchange->closeAllPositions($symbol);
            
            // Log ke database
            \App\Models\ErrorLog::create([
                'source'  => 'KILL_SWITCH',
                'message' => 'Kill Switch diaktifkan dari Dashboard. Semua order dibatalkan & posisi ditutup.',
                'level'   => 'warning'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kill Switch berhasil: ' . ($result['message'] ?? 'Posisi ditutup.')
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal eksekusi Kill Switch: ' . $e->getMessage()], 500);
        }
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
