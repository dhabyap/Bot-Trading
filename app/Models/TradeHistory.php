<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradeHistory extends Model
{
    protected $fillable = [
        'exchange', 'symbol', 'side', 'order_id', 'order_type',
        'amount', 'price', 'cost', 'fee', 'fee_currency',
        'strategy', 'signal_data', 'status', 'profit_loss', 'notes',
    ];

    protected $casts = [
        'signal_data'  => 'array',
        'amount'       => 'float',
        'price'        => 'float',
        'cost'         => 'float',
        'fee'          => 'float',
        'profit_loss'  => 'float',
    ];

    /** Scope: filter berdasarkan simbol */
    public function scopeForSymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    /** Scope: hanya order buy */
    public function scopeBuys($query)
    {
        return $query->where('side', 'buy');
    }

    /** Scope: hanya order sell */
    public function scopeSells($query)
    {
        return $query->where('side', 'sell');
    }
}
