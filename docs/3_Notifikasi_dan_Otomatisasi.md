# 3. Notifikasi dan Otomatisasi

Agar bot berfungsi mandiri 24/7, sistem ini mendukung notifikasi Telegram dan otomatisasi via Scheduler.

## 1. Notifikasi Telegram
Bot mengirim pesan real-time untuk setiap aktivitas:
- Eksekusi Buy/Sell (lengkap dengan harga dan P&L).
- Error sistem (API Timeout, saldo kurang, dll).
- Status Heartbeat (bot masih hidup).

### Setup Telegram:
1. Buat bot via **@BotFather**.
2. Dapatkan Chat ID via **@userinfobot**.
3. Isi `TELEGRAM_BOT_TOKEN` dan `TELEGRAM_CHAT_ID` di `.env`.
4. Test dengan: `php artisan trading:test-telegram`.

## 2. Otomatisasi (Running 24/7)
Anda tidak perlu menekan tombol RUN secara manual. Gunakan salah satu metode berikut:

### Opsi A: Laravel Scheduler (Rekomendasi)
Jalankan perintah ini di background terminal:
```bash
php artisan schedule:work
```
Sistem akan memicu bot setiap 1 menit sesuai jadwal di `Kernel.php`.

### Opsi B: Daemon Mode (Continuous Loop)
Jalankan bot sekali dan ia akan terus berputar:
```bash
php artisan trading:run
```
*(Tanpa flag `--once`, bot akan otomatis mengulang siklus setiap 60 detik).*

## 3. Monitoring Log
Jika terjadi kendala, periksa file log di:
- `storage/logs/trading-bot.log` (Output eksekusi bot)
- `storage/logs/laravel.log` (Error sistem umum)
