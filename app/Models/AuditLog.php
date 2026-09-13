<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
    ];

    // Log tidak pernah di-update setelah tercatat, jadi kolom
    // 'updated_at' sengaja tidak ada di migration & tidak dipakai di sini.
    const UPDATED_AT = null;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Menunjuk ke row spesifik yang kena aksi (Project, Invoice, Client,
     * dst) — bisa null kalau aksinya tidak terkait 1 row data (mis. login).
     */
    public function subject()
    {
        return $this->morphTo();
    }
}
