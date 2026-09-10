<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Master rekening bank KJPP. Dipilih di proposal (`projects.bank_id`) dan
 * dipakai di Invoice + blok "Rekening Bank" proposal .docx.
 */
class Bank extends Model
{
    protected $fillable = [
        'bank_name',
        'branch',
        'account_number',
        'account_name',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /** Rekening baku (fallback bila proposal tidak memilih bank). */
    public static function default(): ?self
    {
        return static::where('is_default', true)->first();
    }

    /** Bentuk array untuk blade invoice / builder proposal. */
    public function toClauseArray(): array
    {
        return [
            'bank_name'      => $this->bank_name,
            'branch'         => $this->branch,
            'account_number' => $this->account_number,
            'account_name'   => $this->account_name,
        ];
    }

    public function getLabelAttribute(): string
    {
        $name = $this->branch ? "{$this->bank_name} ({$this->branch})" : $this->bank_name;

        return "{$name} — {$this->account_number} (a.n. {$this->account_name})";
    }
}
