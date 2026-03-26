# 2. Strategi dan Logika Trading

Dokumen ini menjelaskan "otak" dari bot trading, termasuk indikator teknikal yang digunakan dan alur eksekusi sinyal.

## 1. Strategi: RSI + EMA Crossover
Bot menggunakan konfirmasi ganda untuk mengurangi sinyal palsu:

### A. RSI (Relative Strength Index) - 14 Periode
- Menentukan kondisi jenuh pasar.
- **Oversold (< 35)**: Potensi harga naik.
- **Overbought (> 65)**: Potensi harga turun.

### B. EMA (Exponential Moving Average) - 9 & 21 Periode
- Menentukan arah tren terbaru.
- **Golden Cross**: EMA 9 menembus EMA 21 ke atas (Tren Naik).
- **Death Cross**: EMA 9 menembus EMA 21 ke bawah (Tren Turun).

### C. Kondisi Eksekusi
- **BUY**: Jika RSI < 35 **DAN** terjadi Golden Cross.
- **SELL**: Jika RSI > 65 **DAN** terjadi Death Cross.

## 2. Timeframe & Batas Data
- **Default Timeframe**: `1h` (1 Jam). Memberikan sinyal yang lebih stabil.
- **Data Limit**: Mengambil 100 candle terakhir untuk perhitungan indikator yang akurat.

## 3. Alur Kerja Bot (Siklus)
Setiap kali dijalankan, bot mengikuti alur berikut:
1. **Fetch Data**: Mengambil data harga (OHLCV) terbaru dari Binance.
2. **Analysis**: Menghitung RSI, EMA 9, dan EMA 21 melalui `TradingStrategy.php`.
3. **Execution**: Jika sinyal Buy/Sell muncul, bot mengirim order market ke Binance.
4. **Logging**: Hasil transaksi disimpan ke database `trade_histories` dan dikirim ke Telegram.
