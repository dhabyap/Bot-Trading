# 1. Setup dan Konfigurasi Sistem

Panduan ini mencakup langkah-langkah inisialisasi proyek, instalasi library CCXT, dan konfigurasi koneksi ke Binance API.

## 1. Prasyarat Sistem
- PHP >= 8.2
- Composer >= 2.x
- MariaDB/MySQL atau SQLite
- API Key dari Binance (Live) atau [Binance Testnet](https://testnet.binance.vision)

## 2. Instalasi & Library Utama
- **Laravel**: Framework utama.
- **CCXT**: Library multi-exchange untuk koneksi ke bursa kripto (Binance).
  ```bash
  composer require ccxt/ccxt
  ```

## 3. Konfigurasi Environment (.env)
Sesuaikan file `.env` untuk menentukan mode trading:

```dotenv
EXCHANGE_NAME=binance
EXCHANGE_API_KEY=your_api_key
EXCHANGE_API_SECRET=your_secret_key
EXCHANGE_SANDBOX_MODE=true  # set true untuk Testnet, false untuk Live

TRADING_PAIR=BTC/USDT
TRADING_AMOUNT_USDT=10.0
TRADING_TIMEFRAME=1h
```

## 4. Keamanan API Key
- **Whitelist IP**: Selalu batasi akses API Key hanya dari IP server Anda.
- **Izin Terbatas**: Berikan akses *Read Info* dan *Spot Trading*. **JANGAN** aktifkan *Withdrawal*.
- **Enkripsi**: Sistem secara otomatis mengenkripsi API Key di database jika menggunakan tabel `api_settings`.

## 5. Verifikasi Koneksi
Jalankan perintah berikut untuk memastikan bot dapat terhubung ke Binance:
```bash
php artisan trading:test-connection
```
Perintah ini akan menampilkan Mode (Testnet/Live), saldo aset, dan harga pasar saat ini.
