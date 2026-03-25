# Fase 3: Setup Notifikasi Telegram

## Tujuan
Mengintegrasikan Telegram Bot API ke dalam sistem agar bot trading dapat mengirimkan notifikasi real-time saat terjadi eksekusi order buy/sell, error sistem, bot mulai berjalan, dan ringkasan harian.

---

## Tindakan yang Dilakukan

### 1. File yang Dibuat/Dimodifikasi

| File | Deskripsi |
|---|---|
| `config/telegram.php` | Konfigurasi token dan chat ID |
| `app/Services/TelegramService.php` | Service class notifikasi Telegram |
| `app/Console/Commands/SendTestTelegram.php` | Command test koneksi Telegram |
| `app/Console/Commands/RunTradingBot.php` | Command utama bot (entry point) |
| `app/Console/Kernel.php` | Scheduler dikonfigurasi (setiap menit) |
| `.env` | Ditambahkan blok konfigurasi Exchange + Telegram + Trading |

---

### 2. Cara Mendapatkan Token & Chat ID Telegram

#### Langkah A: Buat Bot Telegram (BotFather)
1. Buka Telegram, cari **@BotFather**
2. Kirim perintah `/newbot`
3. Ikuti instruksi: masukkan nama bot dan username bot (harus diakhiri `bot`)
4. BotFather akan memberikan **Bot Token** format: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`
5. Masukkan token ke `.env`:
   ```dotenv
   TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz
   ```

#### Langkah B: Dapatkan Chat ID
1. Buka browser, akses URL berikut (ganti TOKEN dengan token bot Anda):
   ```
   https://api.telegram.org/bot<TOKEN>/getUpdates
   ```
2. Kirim pesan apa saja ke bot Anda terlebih dahulu di Telegram
3. Di respons JSON, cari nilai `result[0].message.chat.id`
4. Masukkan ke `.env`:
   ```dotenv
   TELEGRAM_CHAT_ID=123456789
   ```

> **Alternatif:** Gunakan bot **@userinfobot** — kirim pesan `/start`, bot akan langsung membalas dengan Chat ID Anda.

---

### 3. Isi `.env` Telegram

```dotenv
TELEGRAM_BOT_TOKEN=TOKEN_DARI_BOTFATHER
TELEGRAM_CHAT_ID=CHAT_ID_ANDA
```

---

### 4. Test Koneksi Telegram

```bash
php artisan trading:test-telegram
```

Output sukses:
```
Mengirim pesan test ke Telegram...
✅ Pesan test berhasil dikirim ke Telegram!
Cek chat Telegram Anda untuk memastikan pesan diterima.
```

---

### 5. Fungsi Notifikasi yang Tersedia

| Metode | Trigger |
|---|---|
| `notifyBuy()` | Order buy berhasil dieksekusi |
| `notifySell()` | Order sell berhasil dieksekusi (include P&L) |
| `notifyError()` | Error sistem terdeteksi |
| `notifyBotStart()` | Bot pertama kali dijalankan |
| `notifyDailySummary()` | Ringkasan harian (bisa dijadwal jam 23:55) |
| `testConnection()` | Test koneksi manual |

---

### 6. Setup Laravel Scheduler (Cron Job)

Bot berjalan via Laravel Scheduler. Konfigurasi scheduler sudah diset di `app/Console/Kernel.php` untuk berjalan **setiap menit** dengan proteksi `withoutOverlapping()`.

#### Di Server Linux/VPS:
```bash
# Buka crontab
crontab -e

# Tambahkan satu baris ini saja:
* * * * * cd /path/to/bot-trading && php artisan schedule:run >> /dev/null 2>&1
```

#### Di Windows (Development):
```bash
# Jalankan scheduler secara manual untuk testing:
php artisan schedule:work
```

---

### 7. Contoh Pesan Telegram

**BUY Order:**
```
🟢 ORDER BUY DIEKSEKUSI
━━━━━━━━━━━━━━━━━━━━━
📌 Pasangan  : BTC/USDT
💰 Harga     : $87,500.00
🔢 Jumlah    : 0.00005714
💵 Total     : $5.00 USDT
📊 Strategi  : RSI_EMA_Crossover
🆔 Order ID  : 123456789
⏰ Waktu     : 2026-03-25 15:00:00 WIB
```

---

## Kendala / Bugs

| Error | Penyebab | Solusi |
|---|---|---|
| `400 Bad Request: chat not found` | Chat ID salah atau belum pernah mengirim pesan ke bot | Kirim pesan `/start` ke bot Anda dulu |
| `401 Unauthorized` | Bot token salah | Cek ulang token dari BotFather |
| Pesan tidak diterima | Bot diblokir oleh user | Unblock bot di Telegram |

---

## Status

✅ **Selesai** — TelegramService dan semua komponen Fase 3 telah dibuat. Scheduler sudah dikonfigurasi.

**Langkah User:**
1. Buat Telegram Bot via BotFather
2. Isi `TELEGRAM_BOT_TOKEN` dan `TELEGRAM_CHAT_ID` di `.env`
3. Jalankan `php artisan trading:test-telegram`

---

*Dibuat: 2026-03-25 | Fase: 3 dari N*
