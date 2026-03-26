# 🤖 Bot Trading Binance — Panduan Penggunaan

> **Stack:** Laravel 10 · CCXT · Binance API · Telegram Bot  
> **Strategi:** RSI-14 + EMA 9/21 Crossover · Aset: BTC/USDT

---

## 📋 Daftar Isi

1. [Prasyarat](#1-prasyarat)
2. [Konfigurasi Awal (.env)](#2-konfigurasi-awal-env)
3. [Mode Testnet vs Live](#3-mode-testnet-vs-live)
4. [Perintah-Perintah Penting](#4-perintah-perintah-penting)
5. [Menjalankan Bot](#5-menjalankan-bot)
6. [Memahami Sinyal Trading](#6-memahami-sinyal-trading)
7. [Notifikasi Telegram](#7-notifikasi-telegram)
8. [Monitoring & Log](#8-monitoring--log)
9. [Pengaturan Parameter](#9-pengaturan-parameter)
10. [Pindah ke Live Trading](#10-pindah-ke-live-trading)
11. [Troubleshooting](#11-troubleshooting)

---

## 1. Prasyarat

Pastikan sudah tersedia:
- PHP >= 8.2 + Composer
- MySQL / MariaDB (database `bot` sudah dibuat)
- Akun Binance (untuk live) atau akun GitHub (untuk testnet)
- Akun Telegram + Bot Token

---

## 2. Konfigurasi Awal (.env)

Edit file `.env` di root project. Isi bagian berikut:

```dotenv
# ── BINANCE API ──────────────────────────────────────────
EXCHANGE_NAME=binance
EXCHANGE_API_KEY=ISI_API_KEY_ANDA_DISINI
EXCHANGE_API_SECRET=ISI_SECRET_KEY_ANDA_DISINI
EXCHANGE_SANDBOX_MODE=true     # true = Testnet | false = Live

# ── TELEGRAM ─────────────────────────────────────────────
TELEGRAM_BOT_TOKEN=ISI_TOKEN_DARI_BOTFATHER
TELEGRAM_CHAT_ID=ISI_CHAT_ID_ANDA

# ── PARAMETER TRADING ────────────────────────────────────
TRADING_PAIR=BTC/USDT
TRADING_AMOUNT_USDT=11.0       # Minimum $10 untuk Binance
TRADING_TIMEFRAME=1h           # 1m | 5m | 15m | 1h | 4h
TRADING_OHLCV_LIMIT=100
```

---

## 3. Mode Testnet vs Live

### 🧪 Testnet (Rekomendasi untuk Pemula)
Gunakan uang virtual, tidak ada risiko kehilangan uang asli.

1. Buka [testnet.binance.vision](https://testnet.binance.vision)
2. Login dengan akun **GitHub**
3. Generate **API Key** di situs tersebut
4. Klik **Faucet** → Request **10.000 USDT** virtual
5. Isi `.env`:
   ```dotenv
   EXCHANGE_API_KEY=testnet_api_key
   EXCHANGE_API_SECRET=testnet_secret_key
   EXCHANGE_SANDBOX_MODE=true
   ```

### 🔴 Live (Uang Asli)
Lihat [bagian 10](#10-pindah-ke-live-trading) setelah testnet stabil.

---

## 4. Perintah-Perintah Penting

| Perintah | Fungsi |
|---|---|
| `php artisan trading:test-connection` | Cek koneksi ke Binance, lihat saldo & harga |
| `php artisan trading:test-telegram` | Kirim pesan test ke Telegram |
| `php artisan trading:run` | Jalankan **satu siklus** bot secara manual |
| `php artisan schedule:work` | Jalankan bot **otomatis setiap menit** (dev) |
| `php artisan migrate` | Buat/update tabel database |
| `php artisan migrate:status` | Cek status migrasi |

---

## 5. Menjalankan Bot

### Langkah Urut Sebelum Pertama Kali

```bash
# 1. Pastikan database sudah ter-migrasi
php artisan migrate

# 2. Test koneksi Binance
php artisan trading:test-connection

# 3. Test notifikasi Telegram (opsional)
php artisan trading:test-telegram

# 4. Jalankan satu siklus manual untuk melihat sinyal
php artisan trading:run
```

### Mode Otomatis (Setiap Menit)

**Di Windows / Development:**
```bash
php artisan schedule:work
```
Biarkan terminal ini berjalan. Bot akan cek sinyal tiap menit secara otomatis.

**Di Server Linux / VPS (Production):**
```bash
# Tambahkan ke crontab hanya SATU baris ini:
crontab -e

# Isi dengan:
* * * * * cd /path/ke/bot-trading && php artisan schedule:run >> /dev/null 2>&1
```

---

## 6. Memahami Sinyal Trading

Bot menggunakan dua indikator teknikal:

### RSI (Relative Strength Index)
Mengukur apakah pasar bereaksi berlebihan.

| Nilai RSI | Kondisi | Artinya |
|---|---|---|
| < 35 | Oversold 📉 | Harga terlalu murah → potensi naik |
| 35 – 65 | Normal ⏸ | Tidak ada aksi (HOLD) |
| > 65 | Overbought 📈 | Harga terlalu mahal → potensi turun |

### EMA Crossover (9 & 21 periode)
Mendeteksi perubahan tren menggunakan dua garis rata-rata.

| Kejadian | Nama | Sinyal |
|---|---|---|
| EMA9 memotong EMA21 **dari bawah ke atas** | Golden Cross ✨ | BUY |
| EMA9 memotong EMA21 **dari atas ke bawah** | Death Cross 💀 | SELL |

### Kapan Bot Eksekusi Order?

```
🟢 BUY  = RSI < 35  DAN Golden Cross terjadi
🔴 SELL = RSI > 65  DAN Death Cross terjadi
⏸ HOLD = Kondisi di atas tidak terpenuhi (mayoritas waktu)
```

> **Normal:** Bot akan HOLD sebagian besar waktu. Sinyal yang valid memang jarang, tapi lebih akurat.

---

## 7. Notifikasi Telegram

Bot otomatis mengirim pesan Telegram saat:

| Event | Isi Pesan |
|---|---|
| ✅ Order BUY | Harga, jumlah koin, total USDT, strategi, Order ID |
| ✅ Order SELL | Harga, jumlah koin, P&L (untung/rugi), Order ID |
| ❌ Error Sistem | Sumber error, pesan, waktu kejadian |

### Cara Setup Telegram
1. Cari **@BotFather** di Telegram → `/newbot` → ikuti instruksi
2. Dapatkan **Token** dari BotFather
3. Start bot Anda, lalu buka:
   ```
   https://api.telegram.org/bot<TOKEN>/getUpdates
   ```
4. Cari `result[0].message.chat.id` → itu **Chat ID** Anda

---

## 8. Monitoring & Log

### Lihat Log Real-time

```bash
# Log khusus output bot (dari scheduler)
tail -f storage/logs/trading-bot.log

# Log error dan info Laravel
tail -f storage/logs/laravel.log
```

### Cek Riwayat Trade di Database

```sql
-- Semua riwayat order
SELECT * FROM trade_histories ORDER BY created_at DESC;

-- Hitung total P&L
SELECT SUM(profit_loss) AS total_pl FROM trade_histories WHERE side = 'sell';

-- Order hari ini
SELECT * FROM trade_histories WHERE DATE(created_at) = CURDATE();
```

### Cek Error Log

```sql
-- Error yang belum direspon
SELECT * FROM error_logs WHERE resolved = 0 ORDER BY created_at DESC;
```

---

## 9. Pengaturan Parameter

Semua parameter bisa diubah di `.env` tanpa mengubah kode:

| Parameter | Nilai Default | Keterangan |
|---|---|---|
| `TRADING_PAIR` | `BTC/USDT` | Pasangan aset (bisa ETH/USDT, BNB/USDT, dll) |
| `TRADING_AMOUNT_USDT` | `11.0` | Modal per order BUY (min. $10 untuk Binance) |
| `TRADING_TIMEFRAME` | `1h` | Timeframe candlestick untuk analisis |
| `TRADING_OHLCV_LIMIT` | `100` | Jumlah candle untuk kalkulasi indikator |

### Rekomendasi Timeframe

| Timeframe | Karakteristik | Cocok Untuk |
|---|---|---|
| `15m` | Aktif, sinyal lebih sering | Testing cepat |
| `1h` | Seimbang, **disarankan** | Trading harian |
| `4h` | Konservatif, sinyal jarang | Trading jangka menengah |
| `1d` | Sangat jarang | Swing trading |

---

## 10. Pindah ke Live Trading

Setelah testnet berjalan stabil (minimal 1-2 minggu, bot menunjukkan profit konsisten), ikuti langkah ini:

### Checklist Sebelum Live

- [ ] Bot testnet profit selama ≥ 7 hari
- [ ] Semua notifikasi Telegram berfungsi
- [ ] Log tidak ada error yang berulang
- [ ] Saldo USDT di akun Binance live tersedia

### Cara Ganti ke Live

1. Buat **API Key baru** di [binance.com](https://www.binance.com) → API Management
   - ✅ Enable Reading
   - ✅ Enable Spot & Margin Trading
   - ❌ JANGAN aktifkan Enable Withdrawals
   - Whitelist IP server Anda

2. Update `.env`:
   ```dotenv
   EXCHANGE_API_KEY=LIVE_API_KEY_ANDA
   EXCHANGE_API_SECRET=LIVE_SECRET_KEY_ANDA
   EXCHANGE_SANDBOX_MODE=false
   ```

3. Clear config cache:
   ```bash
   php artisan config:clear
   ```

4. Test koneksi ulang:
   ```bash
   php artisan trading:test-connection
   ```

5. Jalankan satu siklus manual dulu:
   ```bash
   php artisan trading:run
   ```

> ⚠️ **PENTING:** Mulai dengan `TRADING_AMOUNT_USDT=11.0` (minimum) saat pertama live. Naikkan bertahap setelah yakin sistem stabil.

---

## 11. Troubleshooting

### ❌ Koneksi Gagal

| Error | Solusi |
|---|---|
| `Invalid API key` | Pastikan API Key benar, tidak ada spasi tambahan |
| `Timestamp 1000ms ahead` | Sinkronisasi waktu sistem; bot sudah auto-handle ini |
| `IP not whitelisted` | Tambahkan IP server ke whitelist di Binance |
| Testnet tidak konek | Pastikan key dari `testnet.binance.vision`, bukan Binance utama |

### ❌ Order Gagal

| Error | Solusi |
|---|---|
| `MIN_NOTIONAL` | Naikkan `TRADING_AMOUNT_USDT` ke minimal `11.0` |
| `LOT_SIZE` | Sudah ditangani otomatis oleh kode (pembulatan presisi) |
| `INSUFFICIENT_BALANCE` | Top up USDT di akun (atau request faucet jika testnet) |

### ❌ Telegram Tidak Terkirim

```bash
# Test ulang koneksi Telegram
php artisan trading:test-telegram

# Pastikan isi .env sudah benar
cat .env | grep TELEGRAM
```

### 🔄 Reset Cache Setelah Edit .env
```bash
php artisan config:clear
php artisan cache:clear
```

---

## 📁 Struktur File Penting

```
bot-trading/
├── .env                          ← Semua konfigurasi rahasia
├── app/
│   ├── Console/
│   │   ├── Kernel.php            ← Jadwal scheduler (setiap menit)
│   │   └── Commands/
│   │       ├── RunTradingBot.php     ← 🤖 Bot utama
│   │       ├── TestConnection.php    ← Test koneksi Binance
│   │       └── SendTestTelegram.php  ← Test Telegram
│   └── Services/
│       ├── ExchangeService.php   ← Koneksi & order ke Binance
│       ├── TelegramService.php   ← Pengiriman notifikasi
│       └── TradingStrategy.php   ← Kalkulasi RSI + EMA
├── config/
│   ├── trading.php               ← Config exchange & parameter
│   └── telegram.php              ← Config Telegram
├── database/migrations/
│   ├── ..._trade_histories_table ← Riwayat order buy/sell
│   ├── ..._api_settings_table    ← API Key terenkripsi
│   └── ..._error_logs_table      ← Log error sistem
└── storage/logs/
    ├── laravel.log               ← Log umum Laravel
    └── trading-bot.log           ← Log output bot
```

---

*Panduan ini dibuat pada 2026-03-25. Perbarui setelah ada perubahan konfigurasi.*
