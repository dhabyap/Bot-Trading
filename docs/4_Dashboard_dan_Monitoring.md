# 4. Dashboard dan Monitoring

Dashboard web menyediakan kendali penuh dan visualisasi data trading Anda secara real-time.

## 1. Akses Dashboard
Pastikan server berjalan (`php artisan serve`), lalu akses:
`http://localhost:8000/dashboard`

## 2. Fitur Kendali Utama
- **Run Now**: Menjalankan 1 siklus bot secara manual.
- **Kill Switch (Emergency)**: Tombol merah untuk membatalkan semua order GTC dan menjual seluruh saldo aset ke USDT dalam satu klik. Gunakan ini jika pasar bergerak ekstrem atau ingin berhenti total.

## 3. Metrik Performa
- **Estimated Balance**: Nilai total dompet Anda dalam USDT (USDT + estimasi nilai BTC).
- **Performance Metrics**:
    - **Win Rate**: Persentase kemenangan transaksi Sell.
    - **Total P&L**: Akumulasi profit/loss sejak bot aktif.
    - **Daily P&L**: Keuntungan/kerugian khusus hari ini.
- **API Latency**: Menunjukkan kecepatan ping dari server ke Binance (ms).

## 4. Tabel Transaksi & Order
- **Open Positions**: Posisi yang sedang aktif beserta profit/loss berjalan (floating).
- **Pending Orders**: Daftar order LIMIT yang masih menunggu eksekusi (Open Orders).
- **Trade History**: Riwayat 50 transaksi terakhir.
- **Recent Errors**: Log 20 kendala terakhir yang dialami bot.

## 5. Sinkronisasi Data
Dashboard melakukan refresh otomatis:
- Statistik & Harga: Setiap 30 detik.
- Riwayat & Saldo: Setiap 60 detik.
