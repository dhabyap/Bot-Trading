<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TradingStrategy
{
    /**
     * ============================================================
     * STRATEGI: RSI + EMA CROSSOVER
     * ============================================================
     * Sinyal BUY  : RSI < 35 (oversold) DAN EMA9 memotong ke atas EMA21
     * Sinyal SELL : RSI > 65 (overbought) DAN EMA9 memotong ke bawah EMA21
     * Sinyal HOLD : Tidak ada kondisi yang terpenuhi
     * ============================================================
     *
     * @param  array  $ohlcv  Data OHLCV dari ExchangeService::getOHLCV()
     *                        Format per candle: [timestamp, open, high, low, close, volume]
     * @return array  ['signal' => 'buy'|'sell'|'hold', 'reason' => string, 'indicators' => array]
     */
    public function analyze(array $ohlcv): array
    {
        if (count($ohlcv) < 30) {
            Log::warning('[TradingStrategy] Data OHLCV tidak cukup (' . count($ohlcv) . ' candle). Minimum 30.');
            return $this->holdSignal('Data OHLCV tidak cukup untuk analisis', []);
        }

        // Ekstrak harga close dari OHLCV
        $closes = array_column($ohlcv, 4); // index 4 = close price

        // Hitung indikator
        $rsi     = $this->calculateRSI($closes, 14);
        $ema9    = $this->calculateEMA($closes, 9);
        $ema21   = $this->calculateEMA($closes, 21);

        // Ambil nilai terakhir (current) dan sebelumnya (previous)
        $rsiCurrent    = end($rsi);
        $ema9Current   = end($ema9);
        $ema21Current  = end($ema21);

        $ema9Prev      = $ema9[count($ema9) - 2]   ?? null;
        $ema21Prev     = $ema21[count($ema21) - 2] ?? null;

        $indicators = [
            'rsi'       => round($rsiCurrent, 2),
            'ema9'      => round($ema9Current, 2),
            'ema21'     => round($ema21Current, 2),
            'ema9_prev' => $ema9Prev ? round($ema9Prev, 2) : null,
            'ema21_prev'=> $ema21Prev ? round($ema21Prev, 2) : null,
        ];

        Log::info('[TradingStrategy] Indikator: RSI=' . $rsiCurrent . ' EMA9=' . $ema9Current . ' EMA21=' . $ema21Current);

        // ── KONDISI BUY ──────────────────────────────────────────
        // RSI oversold (<35) DAN EMA9 baru saja menyebrangi EMA21 dari bawah ke atas
        $rsiOversold    = $rsiCurrent < 35;
        $emaBullishCross = $ema9Prev && $ema21Prev &&
                           ($ema9Prev <= $ema21Prev) && ($ema9Current > $ema21Current);

        if ($rsiOversold && $emaBullishCross) {
            return [
                'signal'     => 'buy',
                'reason'     => "RSI oversold ({$rsiCurrent}) + EMA9 golden cross EMA21",
                'indicators' => $indicators,
            ];
        }

        // ── KONDISI SELL ─────────────────────────────────────────
        // RSI overbought (>65) DAN EMA9 baru saja menyebrangi EMA21 dari atas ke bawah
        $rsiOverbought   = $rsiCurrent > 65;
        $emaBearishCross = $ema9Prev && $ema21Prev &&
                           ($ema9Prev >= $ema21Prev) && ($ema9Current < $ema21Current);

        if ($rsiOverbought && $emaBearishCross) {
            return [
                'signal'     => 'sell',
                'reason'     => "RSI overbought ({$rsiCurrent}) + EMA9 death cross EMA21",
                'indicators' => $indicators,
            ];
        }

        // ── HOLD ─────────────────────────────────────────────────
        $reason = "RSI={$rsiCurrent} (normal), tidak ada crossover EMA";
        return $this->holdSignal($reason, $indicators);
    }

    /**
     * Hitung RSI (Relative Strength Index)
     *
     * @param  float[] $closes  Array harga penutupan
     * @param  int     $period  Periode RSI (default: 14)
     * @return float[]
     */
    public function calculateRSI(array $closes, int $period = 14): array
    {
        $rsi     = [];
        $gains   = [];
        $losses  = [];

        for ($i = 1; $i < count($closes); $i++) {
            $change = $closes[$i] - $closes[$i - 1];
            $gains[]  = max($change, 0);
            $losses[] = max(-$change, 0);
        }

        if (count($gains) < $period) {
            return array_fill(0, count($closes), 50.0); // Netral jika data kurang
        }

        // Rata-rata awal
        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

        // RSI pertama
        $rsi[] = $avgLoss == 0 ? 100 : 100 - (100 / (1 + $avgGain / $avgLoss));

        // RSI selanjutnya (Wilder's Smoothing)
        for ($i = $period; $i < count($gains); $i++) {
            $avgGain = ($avgGain * ($period - 1) + $gains[$i]) / $period;
            $avgLoss = ($avgLoss * ($period - 1) + $losses[$i]) / $period;

            $rsi[] = $avgLoss == 0 ? 100 : 100 - (100 / (1 + $avgGain / $avgLoss));
        }

        return $rsi;
    }

    /**
     * Hitung EMA (Exponential Moving Average)
     *
     * @param  float[] $closes  Array harga penutupan
     * @param  int     $period  Periode EMA
     * @return float[]
     */
    public function calculateEMA(array $closes, int $period): array
    {
        if (count($closes) < $period) {
            return array_fill(0, count($closes), $closes[0] ?? 0);
        }

        $multiplier = 2 / ($period + 1);
        $ema        = [];

        // EMA pertama = SMA dari $period data awal
        $ema[] = array_sum(array_slice($closes, 0, $period)) / $period;

        // Hitung EMA selanjutnya
        for ($i = $period; $i < count($closes); $i++) {
            $ema[] = ($closes[$i] - end($ema)) * $multiplier + end($ema);
        }

        return $ema;
    }

    /**
     * Helper: return sinyal HOLD
     */
    private function holdSignal(string $reason, array $indicators): array
    {
        return [
            'signal'     => 'hold',
            'reason'     => $reason,
            'indicators' => $indicators,
        ];
    }
}
