# Fase 1: Inisialisasi & Setup Proyek Bot Trading

## Tujuan
Menyiapkan fondasi proyek Laravel baru yang siap digunakan untuk membangun Bot Trading otomatis. Fase ini mencakup instalasi framework Laravel, instalasi library CCXT untuk koneksi ke bursa kripto, dan konfigurasi environment awal untuk mode produksi (Live Trading).

---

## Tindakan yang Dilakukan

### 1. Prasyarat Sistem
Pastikan perangkat berikut sudah terinstal sebelum memulai:
- PHP >= 8.2
- Composer >= 2.x
- MySQL / MariaDB (untuk database)
- Git

Periksa versi dengan perintah:
```bash
php -v
composer -V
mysql --version
```

---

### 2. Instalasi Laravel Baru

Jalankan perintah berikut di terminal dari direktori tempat proyek akan dibuat:

```bash
# Buat proyek Laravel baru bernama "bot-trading"
composer create-project laravel/laravel bot-trading

# Masuk ke direktori proyek
cd bot-trading
```

---

### 3. Instalasi Package CCXT via Composer

CCXT adalah library multi-exchange yang mendukung 100+ bursa termasuk Tokocrypto (kompatibel dengan standar API Binance).

```bash
# Install CCXT untuk PHP
composer require ccxt/ccxt
```

> **Catatan:** Verifikasi instalasi berhasil dengan memeriksa `vendor/ccxt/ccxt` ada di dalam direktori proyek.

---

### 4. Instalasi Package Pendukung Tambahan

```bash
# Package untuk enkripsi API Key yang lebih robust (opsional, Laravel sudah punya built-in)
# Package untuk HTTP client (sudah include di Laravel 8+)
# Install Guzzle jika belum ada
composer require guzzlehttp/guzzle

# Package untuk logging yang lebih baik (opsional)
# composer require sentry/sentry-laravel
```

---

### 5. Konfigurasi Database

Buat database baru di MySQL:
```sql
CREATE DATABASE bot_trading CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

### 6. Konfigurasi File `.env` untuk Produksi

Salin file template environment:
```bash
cp .env.example .env
php artisan key:generate
```

Edit file `.env` dengan konfigurasi berikut:

```dotenv
# ============================================================
# KONFIGURASI APLIKASI
# ============================================================
APP_NAME="Bot Trading"
APP_ENV=production
APP_KEY=base64:... # otomatis terisi setelah php artisan key:generate
APP_DEBUG=false    # WAJIB false di production untuk keamanan
APP_URL=http://localhost

# ============================================================
# KONFIGURASI DATABASE
# ============================================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bot_trading
DB_USERNAME=root
DB_PASSWORD=your_db_password_here

# ============================================================
# KONFIGURASI EXCHANGE API (TOKOCRYPTO / BINANCE-STANDARD)
# Nilai ini akan disimpan TERENKRIPSI ke database nantinya.
# Variabel .env ini hanya sebagai fallback awal.
# ============================================================
EXCHANGE_NAME=tokocrypto
EXCHANGE_API_KEY=GANTI_DENGAN_API_KEY_PRODUKSI_ANDA
EXCHANGE_API_SECRET=GANTI_DENGAN_SECRET_KEY_PRODUKSI_ANDA
EXCHANGE_SANDBOX_MODE=false  # false = LIVE TRADING dengan uang ASLI

# ============================================================
# KONFIGURASI TELEGRAM BOT
# ============================================================
TELEGRAM_BOT_TOKEN=GANTI_DENGAN_TOKEN_TELEGRAM_BOT_ANDA
TELEGRAM_CHAT_ID=GANTI_DENGAN_CHAT_ID_ANDA

# ============================================================
# KONFIGURASI TRADING (Parameter dasar)
# ============================================================
TRADING_PAIR=BTC/USDT         # Pasangan aset yang diperdagangkan
TRADING_AMOUNT_USDT=10.0      # Jumlah USDT per satu order (mulai kecil!)
TRADING_CHECK_INTERVAL=60     # Interval pengecekan pasar dalam detik (via scheduler)
```

---

### 7. Jalankan Migrasi Awal

```bash
php artisan migrate
```

---

### 8. Struktur Direktori yang Direncanakan

```
bot-trading/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── RunTradingBot.php    # Artisan command utama bot
│   ├── Services/
│   │   ├── ExchangeService.php      # Wrapper CCXT
│   │   ├── TelegramService.php      # Notifikasi Telegram
│   │   └── TradingStrategy.php      # Logika sinyal buy/sell
│   └── Models/
│       ├── TradeHistory.php
│       ├── ApiSetting.php
│       └── ErrorLog.php
├── database/
│   └── migrations/
│       ├── ..._create_trade_histories_table.php
│       ├── ..._create_api_settings_table.php
│       └── ..._create_error_logs_table.php
└── .env                             # Konfigurasi rahasia (JANGAN di-commit ke Git!)
```

---

## ⚠️ PERINGATAN KEAMANAN KRITIS - API KEY PRODUKSI

> **BACA INI SEBELUM MENULIS SATU BARIS KODE PUN**

### 🔴 Aturan WAJIB untuk API Key Live Trading:

1. **JANGAN PERNAH commit `.env` ke Git.**
   Tambahkan `.env` ke `.gitignore` (sudah otomatis di Laravel, tapi verifikasi):
   ```bash
   cat .gitignore | grep .env
   ```

2. **Batasi Hak Akses API Key di Bursa:**
   Saat membuat API Key di Tokocrypto/Binance, aktifkan **hanya** izin yang dibutuhkan:
   - ✅ Read Info (wajib)
   - ✅ Enable Trading (wajib untuk eksekusi order)
   - ❌ Enable Withdrawals (**JANGAN PERNAH aktifkan ini!**)
   - ❌ Enable Universal Transfer (**JANGAN PERNAH!**)

3. **Whitelist IP Address:**
   Di pengaturan API Key bursa, aktifkan pembatasan akses hanya dari IP server bot kamu. Ini mencegah penggunaan API Key dari lokasi lain meskipun key bocor.

4. **Enkripsi API Key di Database:**
   API Key yang disimpan ke database WAJIB dienkripsi menggunakan `Crypt::encryptString()` milik Laravel. **JANGAN simpan plaintext.**
   ```php
   // Cara menyimpan (enkripsi):
   $encrypted = Crypt::encryptString($apiKey);

   // Cara membaca (dekripsi):
   $apiKey = Crypt::decryptString($encrypted);
   ```

5. **Gunakan Amount Kecil untuk Testing Awal:**
   Set `TRADING_AMOUNT_USDT` ke nilai terkecil yang diizinkan exchange (misal $1-$5) saat pertama kali testing live, meskipun sistem sudah terasa siap.

6. **Backup APP_KEY Laravel:**
   `APP_KEY` di `.env` digunakan untuk enkripsi/dekripsi data. Jika hilang, semua API Key terenkripsi di database tidak bisa didekripsi. Backup dengan aman!

---

## Kendala / Bugs

*Belum ada pada fase ini. Akan diperbarui jika ditemukan.*

---

## Status

✅ **Selesai** — Dokumentasi fase inisialisasi telah dibuat. Menunggu eksekusi perintah instalasi oleh pengguna.

---

*Dibuat: 2026-03-25 | Fase: 1 dari N*
