<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Exchange (Binance)
    |--------------------------------------------------------------------------
    | Nilai 'binance' sesuai dengan nama class CCXT.
    | Untuk Binance Testnet, set EXCHANGE_SANDBOX_MODE=true di .env
    | dan gunakan API Key dari https://testnet.binance.vision
    */
    'exchange_name' => env('EXCHANGE_NAME', 'binance'),

    /*
    |--------------------------------------------------------------------------
    | API Key & Secret (dibaca dari .env)
    | Jangan pernah hardcode nilai ini di sini!
    |--------------------------------------------------------------------------
    */
    'api_key'    => env('EXCHANGE_API_KEY'),
    'api_secret' => env('EXCHANGE_API_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Mode Sandbox
    | false = LIVE TRADING (uang asli!)
    | true  = Sandbox / Paper Trading (jika exchange mendukung)
    |--------------------------------------------------------------------------
    */
    'sandbox_mode' => env('EXCHANGE_SANDBOX_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Parameter Trading
    |--------------------------------------------------------------------------
    */
    'trading_pair'        => env('TRADING_PAIR', 'BTC/USDT'),
    'amount_usdt'         => env('TRADING_AMOUNT_USDT', 10.0),
    'check_interval_sec'  => env('TRADING_CHECK_INTERVAL', 60),

    /*
    |--------------------------------------------------------------------------
    | Timeframe untuk Analisis Teknikal
    | Opsi: '1m', '5m', '15m', '30m', '1h', '4h', '1d'
    |--------------------------------------------------------------------------
    */
    'ohlcv_timeframe' => env('TRADING_TIMEFRAME', '1h'),
    'ohlcv_limit'     => env('TRADING_OHLCV_LIMIT', 100),

];
