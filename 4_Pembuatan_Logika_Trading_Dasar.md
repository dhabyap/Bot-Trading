# Fase 4: Pembuatan Logika Trading Dasar

## Tujuan
Mengimplementasikan strategi trading otomatis berbasis analisis teknikal (RSI + EMA Crossover) yang menentukan kapan bot harus membeli (BUY) atau menjual (SELL), mengintegrasikan seluruh komponen (ExchangeService, TelegramService, database), dan menjalankannya via Laravel Scheduler.

---

## Tindakan yang Dilakukan

### 1. File yang Dibuat/Dimodifikasi

| File | Deskripsi |
|---|---|
| `app/Services/TradingStrategy.php` | Kalkulasi RSI-14 + EMA 9/21 Crossover |
| `app/Console/Commands/RunTradingBot.php` | Command utama (orkestrasi penuh) |

---

### 2. Strategi: RSI + EMA Crossover

#### Logika Sinyal

| Sinyal | Kondisi |
|---|---|
| **🟢 BUY** | RSI < 35 (oversold) **DAN** EMA9 baru memotong EMA21 dari bawah ke atas (Golden Cross) |
| **🔴 SELL** | RSI > 65 (overbought) **DAN** EMA9 baru memotong EMA21 dari atas ke bawah (Death Cross) |
| **⏸ HOLD** | Tidak ada kondisi di atas yang terpenuhi |

#### Cara Kerja RSI
- RSI (Relative Strength Index) mengukur **kecepatan dan perubahan** harga
- Nilai 0-35 = pasar oversold → potensi rebound naik (sinyal beli)
- Nilai 65-100 = pasar overbought → potensi koreksi turun (sinyal jual)
- Menggunakan **Wilder's Smoothing Method** (metode standar industri)

#### Cara Kerja EMA Crossover
- **EMA9**: Rata-rata eksponensial 9 periode (cepat/reaktif)
- **EMA21**: Rata-rata eksponensial 21 periode (lambat/stabil)
- **Golden Cross**: EMA9 melewati EMA21 dari bawah → tren naik dimulai
- **Death Cross**: EMA9 melewati EMA21 dari atas → tren turun dimulai

---

### 3. Alur Eksekusi Bot (Setiap Menit)

```
Scheduler (Cron) → trading:run
        │
        ▼
1. ExchangeService::getOHLCV() — Ambil 100 candle terakhir
        │
        ▼
2. TradingStrategy::analyze()  — Hitung RSI + EMA, tentukan sinyal
        │
        ├── BUY  → ExchangeService::createBuyOrder()
        │          TradeHistory::create() [simpan ke DB]
        │          TelegramService::notifyBuy() [kirim notif]
        │
        ├── SELL → ExchangeService::createSellOrder()
        │          Hitung P&L (sell_cost - buy_cost - fee)
        │          TradeHistory::create() [simpan ke DB]
        │          TelegramService::notifySell() [kirim notif]
        │
        └── HOLD → Log saja, tidak ada aksi
```

---

### 4. Cara Menjalankan Bot

#### Manual (Testing):
```bash
# Jalankan satu siklus
php artisan trading:run

# Jalankan scheduler secara lokal (Windows)
php artisan schedule:work
```

#### Otomatis via Cron Job (Linux Server/VPS):
```bash
# Edit crontab
crontab -e

# Tambahkan baris ini:
* * * * * cd /path/ke/bot-trading && php artisan schedule:run >> /dev/null 2>&1
```

---

### 5. Konfigurasi Parameter Strategi (di `.env`)

```dotenv
TRADING_PAIR=BTC/USDT         # Pasangan aset
TRADING_AMOUNT_USDT=5.0       # Modal per satu order BUY (mulai kecil!)
TRADING_TIMEFRAME=1h           # Timeframe analisis (1m, 5m, 15m, 1h, 4h, 1d)
TRADING_OHLCV_LIMIT=100       # Jumlah candle untuk analisis
```

> **Rekomendasi Timeframe:**
> - `1h` atau `4h` untuk bot yang lebih stabil dan sinyal lebih jarang
> - `15m` untuk bot yang lebih aktif
> - Hindari `1m` untuk live trading karena sangat berisiko (noise tinggi)

---

### 6. Monitoring Log

```bash
# Lihat log bot trading secara real-time
tail -f storage/logs/trading-bot.log

# Lihat log Laravel
tail -f storage/logs/laravel.log
```

---

### 7. Perintah Lengkap untuk Memulai

```bash
# 1. Install CCXT
composer require ccxt/ccxt

# 2. Jalankan migrasi database
php artisan migrate

# 3. Test koneksi exchange
php artisan trading:test-connection

# 4. Test notifikasi Telegram  
php artisan trading:test-telegram

# 5. Jalankan satu siklus bot secara manual
php artisan trading:run

# 6. Aktifkan scheduler (development)
php artisan schedule:work
```

---

## ⚠️ Pengingat Keamanan Sebelum Live

- Set `TRADING_AMOUNT_USDT=5.0` (sangat kecil) untuk 5-10 siklus pertama
- Monitor log aktif di terminal saat pertama kali jalan
- Pastikan balance USDT mencukupi untuk setidaknya 3 order

---

## Kendala / Bugs

*Belum ada pada fase ini. Akan diperbarui setelah live testing.*

---

## Status

✅ **Selesai** — Semua komponen logika trading telah diimplementasikan. Sistem siap untuk testing awal.

---

*Dibuat: 2026-03-25 | Fase: 4 dari N*
