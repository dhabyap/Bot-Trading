<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    protected $fillable = [
        'source', 'error_type', 'message', 'stack_trace',
        'context', 'severity', 'telegram_notified', 'resolved', 'resolved_at',
    ];

    protected $casts = [
        'context'            => 'array',
        'telegram_notified'  => 'boolean',
        'resolved'           => 'boolean',
        'resolved_at'        => 'datetime',
    ];

    /**
     * Helper statis: catat error dengan mudah dari mana saja
     */
    public static function log(
        string $source,
        string $message,
        string $severity = 'error',
        ?array $context = null,
        ?\Throwable $exception = null
    ): self {
        return self::create([
            'source'      => $source,
            'error_type'  => $exception ? get_class($exception) : null,
            'message'     => $message,
            'stack_trace' => $exception ? $exception->getTraceAsString() : null,
            'context'     => $context,
            'severity'    => $severity,
        ]);
    }

    /** Scope: error yang belum diresolve */
    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    /** Scope: error yang belum dikirim ke Telegram */
    public function scopeUnnotified($query)
    {
        return $query->where('telegram_notified', false);
    }
}
