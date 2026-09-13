<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'email', 'password', 'role_id', 'is_active', 'dark_mode',
    // Biodata profesi (lihat migration 2024_01_10_000001) — dipakai untuk
    // mengisi blok tanda tangan & Penjelasan Status Penilai pada proposal
    // saat user ini jadi penandatangan.
    'jabatan', 'partner_status', 'mappi_no', 'rmk_no', 'izin_menkeu_no',
    'sk_menkeu_no', 'sk_menkeu_date', 'sttd_ojk_no', 'sttd_ojk_date',
    'klasifikasi',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Pilihan Jabatan (biodata). Hanya "Penanggung Jawab" yang boleh
     * dipilih sebagai penandatangan proposal (lihat scopePenanggungJawab).
     * "Reviewer" memakai set field biodata yang sama dengan Penanggung
     * Jawab (nomor izin Menkeu, SK Menkeu, STTD OJK, klasifikasi).
     */
    public const JABATAN_PELAKSANA_INSPEKSI = 'Pelaksana Inspeksi';
    public const JABATAN_PENILAI            = 'Penilai';
    public const JABATAN_ADMIN              = 'Admin';
    public const JABATAN_PENANGGUNG_JAWAB   = 'Penanggung Jawab';
    public const JABATAN_REVIEWER           = 'Reviewer';

    public const JABATAN_OPTIONS = [
        self::JABATAN_PELAKSANA_INSPEKSI,
        self::JABATAN_PENILAI,
        self::JABATAN_ADMIN,
        self::JABATAN_PENANGGUNG_JAWAB,
        self::JABATAN_REVIEWER,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'dark_mode' => 'boolean',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * User yang boleh dipilih sebagai penandatangan proposal:
     * jabatan "Penanggung Jawab" DAN akun masih aktif.
     */
    public function scopePenanggungJawab($query)
    {
        return $query->where('jabatan', self::JABATAN_PENANGGUNG_JAWAB)
                     ->where('is_active', true);
    }

    /**
     * Helper utama untuk cek izin — dipakai di middleware permission
     * DAN langsung di Blade (mis. @if(auth()->user()->hasPermission('proposals.manage')))
     * untuk sembunyikan tombol yang tidak relevan bagi role user tersebut.
     */
    public function hasPermission(string $key): bool
    {
        if (!$this->role) {
            return false; // user belum punya role = tidak boleh akses apapun
        }

        return $this->role->hasPermission($key);
    }

    public function isAdministrator(): bool
    {
        return $this->role?->slug === Role::ADMINISTRATOR;
    }

    /**
     * Berjabatan "Reviewer" — gerbang akses tombol "Tandai Sudah Direview" /
     * "Kembalikan ke Surveyor" pada alur review SLA Final. SENGAJA dicek
     * dari jabatan (biodata), BUKAN dari Role/izin sistem — siapa pun bisa
     * jadi Reviewer terlepas dari role akunnya (Admin Produksi, dst).
     */
    public function isReviewer(): bool
    {
        return $this->jabatan === self::JABATAN_REVIEWER;
    }

    /**
     * Boleh menekan tombol "Tandai Sudah Direview" / "Kembalikan ke
     * Surveyor" (2026-09-13, feedback user): Reviewer (jabatan) ATAU
     * Administrator (role) — kadang Penanggung Jawab yang me-review
     * langsung lewat akun Administrator-nya, bukan Reviewer. Ini SEBATAS
     * izin menekan tombolnya; siapa yang tercatat sebagai "direview oleh"
     * tetap auth()->id() apa adanya, tidak dipalsukan jadi nama Reviewer.
     */
    public function canActAsReviewer(): bool
    {
        return $this->isReviewer() || $this->isAdministrator();
    }

    /**
     * Nomor surat + tanggalnya dalam satu kalimat untuk dicetak di proposal,
     * mis. "185/MK/SJ/2025 tanggal 23 April 2025". Nomor & tanggal disimpan
     * terpisah (2026-09-15, feedback user); tanggal kosong = nomor saja.
     */
    public function licenseWithDate(string $numberField, string $dateField): ?string
    {
        $number = trim((string) $this->{$numberField});
        if ($number === '') {
            return null;
        }

        $date = $this->{$dateField};

        return $date
            ? $number . ' tanggal ' . \Illuminate\Support\Carbon::parse($date)->translatedFormat('d F Y')
            : $number;
    }
}
