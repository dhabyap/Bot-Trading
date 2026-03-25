# Fase 2: Instalasi CCXT dan Koneksi Live API

## Tujuan
Mengintegrasikan library CCXT (PHP) ke dalam proyek Laravel untuk terkoneksi langsung dengan exchange kripto (Tokocrypto/Binance-compatible) dalam mode live trading, serta membangun struktur database dasar untuk mencatat semua aktivitas bot.

---

## Tindakan yang Dilakukan

### 1. File yang Dibuat

| File | Deskripsi |
|---|---|
| `app/Services/ExchangeService.php` | Service class utama, wrapper semua fungsi CCXT |
| `config/trading.php` | Konfigurasi terpusat semua parameter trading |
| `app/Models/ApiSetting.php` | Model dengan enkripsi/dekripsi API Key otomatis |
| `app/Models/TradeHistory.php` | Model riwayat order buy/sell |
| `app/Models/ErrorLog.php` | Model log error dengan static helper |
| `database/migrations/..._create_trade_histories_table.php` | Migrasi tabel trade histories |
| `database/migrations/..._create_api_settings_table.php` | Migrasi tabel API settings |
| `database/migrations/..._create_error_logs_table.php` | Migrasi tabel error logs |
| `app/Console/Commands/TestConnection.php` | Artisan command test koneksi |

---

### 2. Registrasi Artisan Command

Tambahkan command ke `app/Console/Kernel.php` (Laravel 9 ke bawah), atau pastikan class sudah ada di `app/Console/Commands/` (Laravel 10+ auto-discovers).

Untuk **Laravel 10+**, tidak perlu registrasi manual — command terdeteksi otomatis.

Untuk **Laravel 9 ke bawah**, edit `app/Console/Kernel.php`:
```php
protected $commands = [
    Commands\TestConnection::class,
];
```

---

### 3. Jalankan Migrasi Database

```bash
# Pastikan .env sudah dikonfigurasi dengan benar
# kemudian jalankan:
php artisan migrate
```

Output yang diharapkan:
```
  2026_03_25_000001_create_trade_histories_table ...... 6ms DONE
  2026_03_25_000002_create_api_settings_table ......... 4ms DONE
  2026_03_25_000003_create_error_logs_table ........... 3ms DONE
```

---

### 4. Isi API Key di `.env`

```dotenv
EXCHANGE_NAME=tokocrypto
EXCHANGE_API_KEY=ISI_API_KEY_LIVE_ANDA_DISINI
EXCHANGE_API_SECRET=ISI_SECRET_KEY_LIVE_ANDA_DISINI
EXCHANGE_SANDBOX_MODE=false
TRADING_PAIR=BTC/USDT
TRADING_AMOUNT_USDT=5.0
TRADING_TIMEFRAME=1h
```

---

### 5. Test Koneksi Live

```bash
php artisan trading:test-connection
```

Output sukses yang diharapkan:
```
╔══════════════════════════════════════╗
║   BOT TRADING - Test Koneksi Live   ║
╚══════════════════════════════════════╝

🔴 MODE: LIVE TRADING (uang asli!)
Exchange  : TOKOCRYPTO
Pasangan  : BTC/USDT

Menghubungkan ke exchange...
✓ Mengambil saldo akun...

=== SALDO AKUN ===
 -------- ------------ 
  Aset     Jumlah      
 -------- ------------ 
  USDT     100.00000000
  BTC      0.00000000  
 -------- ------------ 

✓ Mengambil harga ticker BTC/USDT...

=== HARGA BTC/USDT ===
 -------------- --------------- 
  Metrik         Nilai           
 -------------- --------------- 
  Harga Terakhir 87,500.00 USDT 
  ...
 -------------- --------------- 

✅ Koneksi ke exchange BERHASIL!
```

---

### 6. Arsitektur ExchangeService

```
ExchangeService
├── __construct()         — Init CCXT exchange instance
├── getBalance()          — Ambil saldo semua aset
├── getTicker()           — Harga terakhir, bid, ask
├── getOHLCV()            — Data candlestick untuk analisis
├── createBuyOrder()      — Eksekusi market buy (dalam USDT)
├── createSellOrder()     — Eksekusi market sell (dalam koin)
├── getOrder()            — Cek status satu order
├── getOpenOrders()       — Daftar order terbuka
└── testConnection()      — Quick health check
```

---

### 7. Catatan Penting: Tokocrypto & CCXT

Tokocrypto menggunakan standar API yang kompatibel dengan Binance. Jika `tokocrypto` tidak tersedia sebagai nama class CCXT, gunakan `binance` dan tambahkan override URL:

```php
// Di ExchangeService::__construct(), ganti exchange init dengan:
$this->exchange = new \ccxt\binance([
    'apiKey'  => config('trading.api_key'),
    'secret'  => config('trading.api_secret'),
    'urls' => [
        'api' => [
            'public'  => 'https://api.tokocrypto.com',
            'private' => 'https://api.tokocrypto.com',
        ],
    ],
    'enableRateLimit' => true,
]);
```

> **Verifikasi:** Cek daftar exchange yang didukung CCXT dengan `php -r "print_r(array_keys(\ccxt\Exchange::$exchanges));"` di direktori proyek.

---

## Kendala / Bugs

### Kendala yang Mungkin Muncul

| Error | Penyebab | Solusi |
|---|---|---|
| `Class "\ccxt\tokocrypto" not found` | Tokocrypto belum tersedia di CCXT versi terinstal | Gunakan override URL dengan class `binance` |
| `AuthenticationError` | API Key salah atau belum aktif | Verifikasi key di dashboard Tokocrypto |
| `PermissionDenied` | Izin API Key tidak mencukupi | Aktifkan permission Trading di dashboard |
| `Invalid API-key IP whitelist` | Server IP tidak diizinkan | Tambahkan IP server ke whitelist |
| `Rate limit exceeded` | Terlalu banyak request | `enableRateLimit: true` sudah menangani ini |

---

## Status

✅ **Selesai** — Semua file kode Fase 2 telah dibuat. Menunggu eksekusi `php artisan migrate` dan `php artisan trading:test-connection` oleh pengguna.

---

*Dibuat: 2026-03-25 | Fase: 2 dari N*
