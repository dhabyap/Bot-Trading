<?php

namespace App\Console\Commands;

use App\Services\ExchangeService;
use Illuminate\Console\Command;

class TestConnection extends Command
{
    protected $signature   = 'trading:test-connection';
    protected $description = 'Test koneksi ke Binance dan tampilkan saldo akun';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║   BOT TRADING - Test Koneksi Binance ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        $sandboxMode = config('trading.sandbox_mode', false);
        if ($sandboxMode) {
            $this->warn('⚠  MODE: BINANCE TESTNET (tidak ada uang asli)');
            $this->warn('   Pastikan API Key dari: testnet.binance.vision');
        } else {
            $this->error('🔴 MODE: BINANCE LIVE (uang asli!)');
        }

        $this->info('Exchange  : BINANCE');
        $this->info('Pasangan  : ' . config('trading.trading_pair'));
        $this->newLine();

        // Test koneksi
        $this->info('Menghubungkan ke exchange...');

        try {
            $service = new ExchangeService();

            // 1. Test balance
            $this->info('✓ Mengambil saldo akun...');
            $balance = $service->getBalance();

            $this->newLine();
            $this->info('=== SALDO AKUN ===');
            $rows = [];
            foreach ($balance as $currency => $amount) {
                if ($amount > 0) {
                    $rows[] = [$currency, number_format($amount, 8)];
                }
            }
            if (empty($rows)) {
                $this->warn('Saldo semua aset = 0 (atau akun kosong)');
            } else {
                $this->table(['Aset', 'Jumlah'], $rows);
            }

            // 2. Test ticker
            $symbol = config('trading.trading_pair');
            $this->info("✓ Mengambil harga ticker {$symbol}...");
            $ticker = $service->getTicker($symbol);

            $this->newLine();
            $this->info("=== HARGA {$symbol} ===");
            $this->table(
                ['Metrik', 'Nilai'],
                [
                    ['Harga Terakhir', number_format($ticker['last'], 2) . ' USDT'],
                    ['Bid',           number_format($ticker['bid'] ?? 0, 2) . ' USDT'],
                    ['Ask',           number_format($ticker['ask'] ?? 0, 2) . ' USDT'],
                    ['Volume 24H',    number_format($ticker['baseVolume'] ?? 0, 4)],
                    ['Change 24H',    ($ticker['percentage'] ?? 0) . '%'],
                ]
            );

            // 3. Info minimum order Binance
            $symbol  = config('trading.trading_pair');
            $minInfo = $service->getMinOrderInfo($symbol);
            $this->newLine();
            $this->info('=== INFO MINIMUM ORDER BINANCE ===');
            $this->table(
                ['Parameter', 'Nilai'],
                [
                    ['Min. Cost (USDT)', '$' . $minInfo['min_cost']],
                    ['Min. Amount',      $minInfo['min_amount']],
                    ['Presisi Desimal',  $minInfo['precision']],
                ]
            );

            $this->newLine();
            $this->info('✅ Koneksi ke Binance BERHASIL!');
            if ($sandboxMode) {
                $this->warn('Testnet aktif. Ganti EXCHANGE_SANDBOX_MODE=false saat siap live.');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Koneksi Binance GAGAL!');
            $this->error('Error: ' . $e->getMessage());
            $this->newLine();
            $this->warn('Periksa:');
            $this->warn('  1. API Key di .env sudah benar');
            $this->warn('  2. Jika Testnet: gunakan key dari testnet.binance.vision');
            $this->warn('  3. Jika Live: aktifkan izin Enable Reading + Spot Trading');
            $this->warn('  4. Whitelist IP jika diaktifkan di Binance');
            $this->warn('  5. Sinkronisasi waktu sistem (Binance sangat ketat soal ini)');

            return Command::FAILURE;
        }
    }
}
