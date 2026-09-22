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
    'pertanahan_izin_no', 'pertanahan_izin_date', 'ojk_sectors',
    'klasifikasi', 'whatsapp_number', 'avatar_path',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // ===================== FOTO PROFIL (2026-09-15) =====================
    /** Aturan validasi upload foto profil: JPG/PNG maks. 1 MB. */
    public const AVATAR_RULES = ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:1024'];

    public static array $avatarMessages = [
        'avatar.mimes' => 'Foto profil harus berformat JPG atau PNG.',
        'avatar.max'   => 'Ukuran foto profil maksimal 1 MB.',
    ];

    protected static function booted(): void
    {
        static::deleting(fn (User $user) => $user->deleteAvatarFile());
    }

    public function getAvatarUrlAttribute(): ?string
    {
        // asset() mengikuti host yang sedang dipakai (localhost:8899, Tailscale,
        // NAS) — Storage::url() memakai APP_URL yang bisa beda port/host.
        return $this->avatar_path
            ? asset('storage/' . $this->avatar_path) . '?v=' . $this->updated_at?->timestamp
            : null;
    }

    public function getInitialsAttribute(): string
    {
        return \Illuminate\Support\Str::of($this->name)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    }

    /**
     * Terapkan isian form foto profil: file baru menggantikan yang lama,
     * centang "hapus foto" mengosongkannya. Belum di-save().
     */
    public function applyAvatarUpload(\Illuminate\Http\Request $request): void
    {
        if ($request->hasFile('avatar')) {
            $newPath = self::storeCompressedAvatar($request->file('avatar'));
            // Foto lama langsung dibuang dari server begitu foto baru tersimpan
            // (2026-09-15, feedback user) — tidak ada file yatim di avatars/.
            $this->deleteAvatarFile();
            $this->avatar_path = $newPath;
        } elseif ($request->boolean('remove_avatar')) {
            $this->deleteAvatarFile();
            $this->avatar_path = null;
        }
    }

    /**
     * Simpan foto profil sebagai JPG persegi maks. 512px kualitas 82 — hasil
     * akhirnya selalu puluhan s/d ratusan KB walau browser tidak sempat
     * meng-crop (mis. browser lama). Tanpa GD: simpan file apa adanya
     * (tetap dibatasi 1 MB oleh validasi).
     */
    private static function storeCompressedAvatar(\Illuminate\Http\UploadedFile $file): string
    {
        $src = function_exists('imagecreatefromstring') ? @imagecreatefromstring((string) file_get_contents($file->getRealPath())) : false;
        if (! $src) {
            return $file->store('avatars', 'public');
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $side = min($w, $h);                       // potong tengah jadi persegi
        $out  = min(512, $side);
        $dst  = imagecreatetruecolor($out, $out);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // PNG transparan -> putih
        imagecopyresampled($dst, $src, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), $out, $out, $side, $side);

        ob_start();
        imagejpeg($dst, null, 82);
        $jpeg = ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);

        $path = 'avatars/' . \Illuminate\Support\Str::random(40) . '.jpg';
        \Illuminate\Support\Facades\Storage::disk('public')->put($path, $jpeg);

        return $path;
    }

    public function deleteAvatarFile(): void
    {
        if ($this->avatar_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($this->avatar_path);
        }
    }

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

    /**
     * Sektor jasa keuangan pada Surat Tanda Terdaftar OJK (2026-09-22).
     * Dicetak berurutan sebagai daftar bernomor di bab Penjelasan Status
     * Penilai. Tiap penilai bisa punya lingkup berbeda.
     */
    public const OJK_SECTORS = [
        'Perbankan',
        'Pasar Modal, Keuangan Derivatif dan Bursa Karbon dengan ruang lingkup kegiatan Penilai Properti',
        'Perasuransian, Penjaminan dan Dana Pensiun',
        'Lembaga Pembiayaan, Perusahaan Modal Ventura, Lembaga Keuangan Mikro dan Lembaga Jasa Keuangan Lainnya',
        'Inovasi Teknologi Sektor Keuangan serta Aset Keuangan Digital dan Aset Kripto',
    ];

    /**
     * Gabungkan centang sektor baku (urut sesuai daftar baku) dengan sektor
     * tambahan yang diketik satu per baris. Null bila kosong semua.
     */
    public static function composeOjkSectors(array $checked, ?string $other): ?array
    {
        $extra = collect(preg_split('/\r\n|\r|\n/', (string) $other))
            ->map(fn ($l) => trim(rtrim(trim($l), ';.')))
            ->filter()
            ->reject(fn ($l) => in_array($l, self::OJK_SECTORS, true));

        $all = collect(self::OJK_SECTORS)->intersect($checked)->values()->merge($extra)->unique()->values()->all();

        return $all ?: null;
    }

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
            'ojk_sectors' => 'array',
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
     * Melihat angka SELURUH kantor di Beranda & tidak butuh tab "Proyek Saya"
     * (2026-09-13, feedback user): role Administrator / Admin Produksi /
     * General Admin atau jabatan Admin. Jabatan Reviewer, Penilai & Pelaksana
     * Inspeksi tetap berbasis tugas pribadi walaupun role akunnya admin.
     */
    public function seesOfficeWide(): bool
    {
        if (in_array($this->jabatan, [self::JABATAN_REVIEWER, self::JABATAN_PENILAI, self::JABATAN_PELAKSANA_INSPEKSI], true)) {
            return false;
        }

        return in_array($this->role?->slug, [Role::ADMINISTRATOR, Role::ADMIN_PRODUKSI, Role::ADMIN_KEUANGAN], true)
            || $this->jabatan === self::JABATAN_ADMIN;
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

    /** Nomor WhatsApp selalu disimpan "628..." supaya bisa di-mention bot. */
    public function setWhatsappNumberAttribute(?string $value): void
    {
        $this->attributes['whatsapp_number'] = \App\Services\WhatsAppNotifier::normalizeNumber($value);
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
