<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'client_name',
        'client_type',
        'address',
        'contact_person',
        'phone',
        'email',
    ];

    /**
     * =========================================================================
     * DETEKSI KLIEN KEMBAR (2026-09-15, feedback user) — dipakai peringatan di
     * modal "Tambah Klien Baru". Teks diseragamkan dulu supaya beda titik, koma,
     * huruf besar, "PT/Tbk", atau singkatan alamat (Jl/No/Kav) tidak dianggap
     * berbeda, lalu dihitung persentase kemiripannya.
     * =========================================================================
     */
    public static function normalizeName(?string $name): string
    {
        $s = mb_strtolower((string) $name);
        $s = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s);
        $s = preg_replace('/\b(pt|tbk|persero|cv|ud|pd)\b/u', ' ', $s);

        return trim(preg_replace('/\s+/', ' ', $s));
    }

    public static function normalizeAddress(?string $address): string
    {
        $s = mb_strtolower((string) $address);
        $s = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s);
        $aliases = [
            'jl' => 'jalan', 'jln' => 'jalan', 'no' => 'nomor', 'nmr' => 'nomor', 'kav' => 'kavling',
            'gd' => 'gedung', 'gdg' => 'gedung', 'lt' => 'lantai', 'blk' => 'blok',
            'kel' => 'kelurahan', 'kec' => 'kecamatan', 'kab' => 'kabupaten', 'prov' => 'provinsi',
        ];
        $words = preg_split('/\s+/', trim($s), -1, PREG_SPLIT_NO_EMPTY);

        return implode(' ', array_map(fn ($w) => $aliases[$w] ?? $w, $words));
    }

    /** Persentase kemiripan dua teks yang sudah dinormalisasi (0–100). */
    public static function similarity(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0;
        }
        if ($a === $b) {
            return 100;
        }
        similar_text($a, $b, $percent);

        return $percent;
    }

    /**
     * Persentase kata dari teks yang LEBIH PENDEK yang juga muncul di teks lain
     * (0–100). Menangkap alamat yang diketik sebagian, mis. tanpa nama gedung.
     */
    public static function tokenOverlap(string $a, string $b): float
    {
        $ta = array_unique(preg_split('/\s+/', $a, -1, PREG_SPLIT_NO_EMPTY));
        $tb = array_unique(preg_split('/\s+/', $b, -1, PREG_SPLIT_NO_EMPTY));
        [$short, $long] = count($ta) <= count($tb) ? [$ta, $tb] : [$tb, $ta];
        if (count($short) < 3) {
            return 0;
        }

        return count(array_intersect($short, $long)) / count($short) * 100;
    }

    /**
     * Proyek-proyek di mana klien ini berperan sebagai Pemberi Tugas.
     */
    public function projectsAsInstructingClient()
    {
        return $this->hasMany(Project::class, 'instructing_client_id');
    }

    /** Proyek yang memakai klien ini sebagai "Nama Klien" (2026-09-14). */
    public function projectsAsNamedClient()
    {
        return $this->hasMany(Project::class, 'client_id');
    }

    /** Proyek yang memakai klien ini sebagai "Pihak yang Menyetujui". */
    public function projectsAsApprover()
    {
        return $this->hasMany(Project::class, 'approver_client_id');
    }

    /**
     * Proyek-proyek di mana klien ini berperan sebagai Pengguna Laporan
     * (many-to-many lewat pivot project_intended_users).
     */
    public function projectsAsIntendedUser()
    {
        return $this->belongsToMany(
            Project::class,
            'project_intended_users',
            'client_id',
            'project_id'
        )->withTimestamps();
    }
}
