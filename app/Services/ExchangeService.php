<?php

namespace App\Services;

use ccxt\binance;
use Illuminate\Support\Facades\Log;

class ExchangeService
{
    protected binance $exchange;
    protected string $symbol;

    public function __construct()
    {
        $this->symbol = config('trading.trading_pair', 'BTC/USDT');

        $this->exchange = new binance([
            'apiKey'          => config('trading.api_key'),
            'secret'          => config('trading.api_secret'),
            'enableRateLimit' => true,       // Wajib: hormati rate limit Binance
            'options'         => [
                'defaultType'          => 'spot',
                'adjustForTimeDifference' => true, // Otomatis sinkronkan waktu server
            ],
        ]);

        // Binance Testnet (aman untuk testing tanpa uang asli)
        if (config('trading.sandbox_mode', false)) {
            $this->exchange->set_sandbox_mode(true);
            Log::warning('[ExchangeService] BINANCE TESTNET aktif. Gunakan API Key dari testnet.binance.vision');
        } else {
            Log::info('[ExchangeService] BINANCE LIVE MODE aktif. Order menggunakan uang nyata!');
        }
    }

    /**
     * Ambil saldo akun (semua aset dengan saldo > 0)
     */
    public function getBalance(): array
    {
        try {
            $balance = $this->exchange->fetch_balance();
            return $balance['total'] ?? [];
        } catch (\Exception $e) {
            Log::error('[ExchangeService] Gagal ambil saldo: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ambil harga ticker terkini
     */
    public function getTicker(?string $symbol = null): array
    {
        try {
            return $this->exchange->fetch_ticker($symbol ?? $this->symbol);
        } catch (\Exception $e) {
            Log::error('[ExchangeService] Gagal ambil ticker: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ambil data OHLCV (candlestick) untuk analisis teknikal
     *
     * @param string $timeframe '1m','5m','15m','1h','4h','1d'
     * @param int    $limit     Jumlah candle
     */
    public function getOHLCV(?string $symbol = null, string $timeframe = '1h', int $limit = 100): array
    {
        try {
            return $this->exchange->fetch_ohlcv($symbol ?? $this->symbol, $timeframe, null, $limit);
        } catch (\Exception $e) {
            Log::error('[ExchangeService] Gagal ambil OHLCV: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Buat Market Order BUY — Binance menggunakan quoteOrderQty
     *
     * Binance mendukung pembelian langsung dengan nominal USDT (quoteOrderQty).
     * Cara ini lebih akurat karena tidak perlu kalkulasi harga manual.
     *
     * @param float $amountUsdt Jumlah USDT yang digunakan
     */
    public function createBuyOrder(float $amountUsdt, ?string $symbol = null): array
    {
        try {
            $symbol = $symbol ?? $this->symbol;

            // Binance: gunakan quoteOrderQty untuk beli dengan nominal USDT
            // CCXT menyediakan create_order dengan parameter ini
            $order = $this->exchange->create_order(
                $symbol,
                'market',   // type
                'buy',      // side
                null,       // amount (null karena kita pakai quoteOrderQty)
                null,       // price (null untuk market order)
                ['quoteOrderQty' => $amountUsdt] // beli dengan USDT langsung
            );

            Log::info(sprintf(
                '[ExchangeService] BUY ORDER Binance: %s USDT untuk %s | Order ID: %s',
                $amountUsdt,
                $symbol,
                $order['id'] ?? 'unknown'
            ));

            return $order;

        } catch (\Exception $e) {
            Log::error('[ExchangeService] Gagal BUY Order Binance: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Buat Market Order SELL
     *
     * @param float $amount Jumlah koin yang akan dijual (bukan USDT)
     */
    public function createSellOrder(float $amount, ?string $symbol = null): array
    {
        try {
            $symbol = $symbol ?? $this->symbol;

            // Bulatkan sesuai presisi Binance (hindari error "LOT_SIZE")
            $market    = $this->exchange->market($symbol);
            $precision = $market['precision']['amount'] ?? 8;
            $amount    = round($amount, (int) $precision);

            $order = $this->exchange->create_market_sell_order($symbol, $amount);

            Log::info(sprintf(
                '[ExchangeService] SELL ORDER Binance: %s %s | Order ID: %s',
                $amount,
                $symbol,
                $order['id'] ?? 'unknown'
            ));

            return $order;

        } catch (\Exception $e) {
            Log::error('[ExchangeService] Gagal SELL Order Binance: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ambil detail order berdasarkan ID
     */
    public function getOrder(string $orderId, ?string $symbol = null): array
    {
        try {
            return $this->exchange->fetch_order($orderId, $symbol ?? $this->symbol);
        } catch (\Exception $e) {
            Log::error('[ExchangeService] Gagal ambil order: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ambil daftar order terbuka
     */
    public function getOpenOrders(?string $symbol = null): array
    {
        try {
            return $this->exchange->fetch_open_orders($symbol ?? $this->symbol);
        } catch (\Exception $e) {
            Log::error('[ExchangeService] Gagal ambil open orders: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Info minimum order Binance untuk simbol tertentu
     * Berguna untuk validasi sebelum eksekusi
     */
    public function getMinOrderInfo(string $symbol): array
    {
        try {
            $this->exchange->load_markets();
            $market = $this->exchange->market($symbol);
            return [
                'min_cost'   => $market['limits']['cost']['min']   ?? 10,
                'min_amount' => $market['limits']['amount']['min'] ?? 0,
                'precision'  => $market['precision']['amount']     ?? 8,
            ];
        } catch (\Exception $e) {
            return ['min_cost' => 10, 'min_amount' => 0, 'precision' => 8];
        }
    }

    /**
     * Test koneksi ke Binance
     */
    public function testConnection(): bool
    {
        try {
            $balance = $this->getBalance();
            Log::info('[ExchangeService] Koneksi Binance berhasil. Saldo: ' . json_encode($balance));
            return true;
        } catch (\Exception $e) {
            Log::error('[ExchangeService] Koneksi Binance GAGAL: ' . $e->getMessage());
            return false;
        }
    }
}
