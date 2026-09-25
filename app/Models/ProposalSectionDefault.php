<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Teks baku SATU bab proposal untuk SATU tujuan penilaian.
 *
 * Dipakai sebagai teks default saat proposal dicetak: kalau proyeknya sendiri
 * tidak punya override (proposal_section_texts), teks di sini yang dipakai;
 * kalau di sini juga kosong, barulah teks dari config/proposal_clauses.php.
 *
 * Lihat migration 2024_01_23_000025 & App\Services\ProposalDocxBuilder.
 */
class ProposalSectionDefault extends Model
{
    /** Nilai proposal_purpose yang berarti "berlaku untuk semua tujuan". */
    public const SEMUA_TUJUAN = '';

    protected $fillable = [
        'proposal_purpose',
        'section_key',
        'body',
        'updated_by_user_id',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * [section_key => body] yang berlaku untuk sebuah tujuan penilaian.
     * Baris "semua tujuan" jadi dasar, lalu ditimpa baris khusus tujuan itu.
     */
    public static function untukTujuan(?string $purpose): array
    {
        $rows = static::query()
            ->whereIn('proposal_purpose', array_unique([self::SEMUA_TUJUAN, (string) $purpose]))
            ->get();

        $umum    = $rows->where('proposal_purpose', self::SEMUA_TUJUAN)->pluck('body', 'section_key')->all();
        $khusus  = $rows->where('proposal_purpose', (string) $purpose)
            ->where('proposal_purpose', '!=', self::SEMUA_TUJUAN)
            ->pluck('body', 'section_key')->all();

        return array_filter(array_merge($umum, $khusus), fn ($b) => trim((string) $b) !== '');
    }
}
