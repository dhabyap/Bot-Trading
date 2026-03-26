# Fase 6: Dashboard Monitoring Web

## Tujuan
Membangun antarmuka web dashboard real-time untuk memonitor seluruh aktivitas bot trading tanpa perlu membuka terminal atau database secara manual.

---

## Tindakan yang Dilakukan

### 1. File yang Dibuat/Dimodifikasi

| File | Deskripsi |
|---|---|
| `app/Http/Controllers/DashboardController.php` | Controller dengan 4 endpoint (halaman + 3 API JSON) |
| `routes/web.php` | Route dashboard + API endpoint |
| `resources/views/dashboard/index.blade.php` | Tampilan dashboard dark mode premium |

---

### 2. Cara Mengakses Dashboard

```bash
# Pastikan server berjalan
php artisan serve

# Buka browser ke:
http://localhost:8000/dashboard
```

---

### 3. Fitur Dashboard

| Fitur | Deskripsi | Auto-Refresh |
|---|---|---|
| 💰 Harga BTC/USDT | Harga real-time + % perubahan 24H | 30 detik |
| 🤖 Status Bot | **Indikator Aktif/Tidak** (berdasarkan last run) | 30 detik |
| 📈 Sinyal Akhir | Sinyal teknikal terakhir + Nilai RSI/EMA | 30 detik |
| 📊 P&L Hari Ini | Profit/Loss akumulasi hari ini | 30 detik |
| 📤 Test Telegram | Tombol untuk kirim status bot ke Telegram | Manual |
| 💼 Saldo Akun | USDT, BTC, ETH dari Binance | 60 detik |
| 📌 Posisi Terbuka | Buy price, current price, Unrealized P&L | 30 detik |
| 🕐 Riwayat Trade | 50 order terakhir (buy/sell) | 1 menit |
| ⚠️ Error Log | 20 error terbaru + status resolved | 1 menit |

---

### 4. Arsitektur Dashboard (Cara Kerja)

```
Browser (setiap 30 detik)
    │
    ├─ GET /api/dashboard/stats  → ticker + saldo + P&L + posisi + BOT STATUS
    ├─ GET /api/dashboard/trades → 50 riwayat trade terbaru
    ├─ GET /api/dashboard/errors → 20 error log terbaru
    └─ POST /api/dashboard/send-telegram → kirim status ke bot Telegram

DashboardController
    ├─ apiStats()    → Baca Cache 'bot_running' & 'bot_last_run'
    │                 ExchangeService::getTicker(), ::getBalance()
    ├─ sendTelegram() → Kirim ringkasan status bot via TelegramService
```

**Mekanisme Status Bot:**
Setiap kali `trading:run` dijalankan (oleh scheduler), bot akan menyimpan timestamp ke Cache. Jika dalam 90 detik terakhir bot tidak jalan, dashboard akan menampilkan status **STOPPED**.

**Catatan cache:** Data harga dan saldo di-cache agar tidak spam API Binance. Binance rate limit: 1200 request/menit.

---

### 5. Tampilan Dashboard

**Dark Mode dengan elemen:**
- Header sticky: nama bot, mode (TESTNET/LIVE), dot animasi hijau (tanda aktif), waktu update terakhir
- **Kartu Harga BTC:** besar dan dominan, dengan badge % change berwarna hijau/merah
- **Kartu P&L:** berubah warna (hijau/merah) sesuai profit/loss, dengan glow effect
- **Kartu Volume 24H**
- **Tabel Saldo:** menampilkan semua aset > 0
- **Posisi Terbuka:** menampilkan unrealized P&L real-time
- **Tabel Riwayat Trade:** badge warna untuk buy/sell, kolom P&L berwarna
- **Tabel Error Log:** badge severity, status resolved/aktif

---

### 6. Menjalankan Dashboard + Bot Bersamaan

Gunakan **2 terminal** berbeda:

```bash
# Terminal 1: Scheduler bot (cek sinyal setiap menit)
php artisan schedule:work

# Terminal 2: Web server dashboard
php artisan serve

# Buka browser:
http://localhost:8000/dashboard
```

---

### 7. Konfigurasi Refresh Interval (Opsional)

Edit bagian script di `resources/views/dashboard/index.blade.php`:

```javascript
const REFRESH_STATS  = 30000;  // 30 detik (stats, ticker, saldo)
const REFRESH_TRADES = 60000;  // 60 detik (tabel trade + error)
```

---

## Kendala / Bugs

| Issue | Penyebab | Solusi |
|---|---|---|
| Dashboard blank / loading terus | Server belum jalan | `php artisan serve` |
| Data tidak muncul | API Key Exchange belum diisi | Isi `.env` lalu `php artisan config:clear` |
| Error CORS di console | Request lintas port | Sudah dihandle via Laravel route (tidak pakai CDN API) |

---

## Status

✅ **Selesai** — Dashboard monitoring real-time berhasil dibuat dan siap diakses.

**Akses:** `http://localhost:8000/dashboard`

---

*Dibuat: 2026-03-25 | Fase: 6 — Dashboard Monitoring Web*
