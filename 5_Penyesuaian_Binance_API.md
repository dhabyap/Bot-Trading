# Fase 5: Penyesuaian Integrasi Binance API

## Tujuan
Mengganti integrasi exchange dari Tokocrypto ke **Binance** agar dapat menggunakan API resmi Binance secara gratis, termasuk memanfaatkan **Binance Testnet** untuk testing aman sebelum live trading dengan uang asli.

---

## Tindakan yang Dilakukan

### 1. File yang Dimodifikasi

| File | Perubahan |
|---|---|
| `.env` | `EXCHANGE_NAME=binance`, sandbox mode aktif |
| `app/Services/ExchangeService.php` | Ditulis ulang khusus Binance |
| `app/Console/Commands/TestConnection.php` | Info testnet + min order info |
| `config/trading.php` | Default exchange → binance |

---

### 2. Perbedaan Kritis: Tokocrypto vs Binance

| Aspek | Tokocrypto | Binance |
|---|---|---|
| Market Buy | Kalkulasi manual (USDT ÷ harga) | `quoteOrderQty` (langsung dalam USDT) |
| Presisi SELL | Tidak kritis | Wajib sesuai `LOT_SIZE` exchange |
| Testnet | Tidak ada | ✅ `testnet.binance.vision` |
| Sinkronisasi Waktu | Manual | Otomatis (`adjustForTimeDifference`) |

---

### 3. Cara Dapat API Key Binance

#### A. API Key LIVE (Real Trading)
1. Login ke [binance.com](https://www.binance.com)
2. Kanan atas → **Profile → API Management**
3. Klik **Create API** → pilih **System Generated**
4. Beri label, misal: `BotTradingLaravel`
5. Aktifkan izin: ✅ **Enable Reading** + ✅ **Enable Spot & Margin Trading**
6. ❌ **JANGAN** aktifkan Enable Withdrawals!
7. Tambahkan IP server ke whitelist
8. Simpan ke `.env`:

```dotenv
EXCHANGE_API_KEY=API_KEY_DARI_BINANCE
EXCHANGE_API_SECRET=SECRET_KEY_DARI_BINANCE
EXCHANGE_SANDBOX_MODE=false
```

#### B. API Key TESTNET (Testing Aman — Rekomendasi Pertama)
1. Buka [testnet.binance.vision](https://testnet.binance.vision)
2. Login dengan akun GitHub
3. Generate API Key
4. Simpan ke `.env`:

```dotenv
EXCHANGE_API_KEY=TESTNET_API_KEY
EXCHANGE_API_SECRET=TESTNET_SECRET_KEY
EXCHANGE_SANDBOX_MODE=true
```

> **Keunggulan Testnet:** Anda mendapatkan saldo BTC/USDT virtual untuk testing lengkap tanpa risiko kehilangan uang asli.

---

### 4. Cara Market Buy di Binance (via `quoteOrderQty`)

**Binance menggunakan parameter khusus** untuk market buy dengan nominal USDT:

```php
// Beli BTC dengan tepat $10 USDT (Binance menghitung jumlah BTC-nya)
$order = $exchange->create_order(
    'BTC/USDT',
    'market',
    'buy',
    null,
    null,
    ['quoteOrderQty' => 10.0]  // 10 USDT
);
```

Ini lebih akurat dibanding kalkulasi manual `amount = USDT / price`.

---

### 5. Minimum Order Binance

| Pasangan | Min. Cost | Min. Amount |
|---|---|---|
| BTC/USDT | ~$10 USDT | Bergantung harga |
| ETH/USDT | ~$10 USDT | Bergantung harga |
| BNB/USDT | ~$10 USDT | Bergantung harga |

> ⚠️ Karena minimum order Binance adalah $10 USDT, pastikan `TRADING_AMOUNT_USDT` di `.env` **≥ 10**.

---

### 6. Jalankan Test Koneksi

```bash
# Test ke Binance (Testnet atau Live sesuai .env)
php artisan trading:test-connection
```

Output akan menampilkan:
- Mode (Testnet / Live)
- Saldo semua aset
- Harga ticker BTC/USDT
- Info minimum order

---

## Kendala / Bugs yang Mungkin Muncul

| Error | Penyebab | Solusi |
|---|---|---|
| `Timestamp for this request was 1000ms ahead` | Jam sistem tidak sinkron | CCXT otomatis fix ini dengan `adjustForTimeDifference` |
| `LOT_SIZE` error saat SELL | Presisi amount salah | Sudah diatasi dengan `round()` sesuai market precision |
| `MIN_NOTIONAL` error | Amount order di bawah minimum | Naikkan `TRADING_AMOUNT_USDT` ke minimal 10 |
| `Invalid API-key, IP, or permissions` | IP tidak diwhitelist | Tambahkan IP ke Binance API settings |
| Testnet tidak bisa connect | Pakai key live di testnet | Gunakan key dari `testnet.binance.vision` |

---

## Status

✅ **Selesai** — Semua komponen disesuaikan untuk Binance API.

**Langkah User:**
1. Pilih: Testnet (aman) atau Live (uang asli)
2. Dapatkan API Key sesuai pilihan
3. Isi `.env` dengan API Key
4. Jalankan `php artisan trading:test-connection`

---

*Dibuat: 2026-03-25 | Fase: 5 — Penyesuaian Binance API*
