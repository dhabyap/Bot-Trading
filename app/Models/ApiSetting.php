<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ApiSetting extends Model
{
    protected $fillable = [
        'exchange_name',
        'label',
        'api_key_encrypted',
        'api_secret_encrypted',
        'is_active',
        'sandbox_mode',
        'permissions',
        'last_tested_at',
        'last_test_success',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'sandbox_mode'      => 'boolean',
        'last_test_success' => 'boolean',
        'permissions'       => 'array',
        'last_tested_at'    => 'datetime',
    ];

    /**
     * Simpan API Key (otomatis dienkripsi)
     */
    public function setApiKeyAttribute(string $value): void
    {
        $this->attributes['api_key_encrypted'] = Crypt::encryptString($value);
    }

    /**
     * Baca API Key (otomatis didekripsi)
     */
    public function getApiKeyAttribute(): string
    {
        return Crypt::decryptString($this->attributes['api_key_encrypted']);
    }

    /**
     * Simpan API Secret (otomatis dienkripsi)
     */
    public function setApiSecretAttribute(string $value): void
    {
        $this->attributes['api_secret_encrypted'] = Crypt::encryptString($value);
    }

    /**
     * Baca API Secret (otomatis didekripsi)
     */
    public function getApiSecretAttribute(): string
    {
        return Crypt::decryptString($this->attributes['api_secret_encrypted']);
    }

    /**
     * Scope: Ambil setting yang sedang aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
