<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "Penanggung Jawab baku kantor" dihapus dari dropdown proposal (2026-09-15,
     * feedback user) — sudah ada akun user untuknya. Data baku di
     * config('kjpp.signatory') dipindahkan ke biodata user tsb (hanya kolom
     * yang masih KOSONG, isian yang sudah ada tidak ditimpa), lalu proyek yang
     * belum punya Penanggung Jawab diarahkan ke user itu. Hasil cetak proposal
     * tidak berubah karena datanya identik dengan config.
     *
     * User dicari berdasarkan nama persis config signatory + jabatan
     * "Penanggung Jawab". Tidak ketemu (mis. instalasi baru) = dilewati.
     */
    private const MONTHS = [
        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6,
        'juli' => 7, 'agustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
    ];

    public function up(): void
    {
        $cfg = config('kjpp.signatory');
        if (! $cfg) {
            return;
        }

        $user = DB::table('users')
            ->where('name', $cfg['name'])
            ->where('jabatan', 'Penanggung Jawab')
            ->first();
        if (! $user) {
            return;
        }

        [$skNo, $skDate]   = self::split($cfg['sk_menkeu_no'] ?? '');
        [$kepNo, $kepDate] = self::split($cfg['ojk_kep_no'] ?? '');
        [$sttdNo, $sttdDate] = self::split($cfg['sttd_ojk_no'] ?? '');

        $candidates = [
            'partner_status' => $cfg['title'] ?? null,
            'izin_menkeu_no' => $cfg['izin_pp_no'] ?? null,
            'mappi_no'       => $cfg['mappi_no'] ?? null,
            'rmk_no'         => $cfg['rmk_no'] ?? null,
            'klasifikasi'    => $cfg['klasifikasi'] ?? null,
            'sk_menkeu_no'   => $skNo,
            'sk_menkeu_date' => $skDate,
            'ojk_kep_no'     => $kepNo,
            'ojk_kep_date'   => $kepDate,
            'sttd_ojk_no'    => $sttdNo,
            'sttd_ojk_date'  => $sttdDate,
        ];

        $updates = [];
        foreach ($candidates as $column => $value) {
            if (($value ?? '') !== '' && trim((string) $user->{$column}) === '') {
                $updates[$column] = $value;
            }
        }
        if ($updates) {
            DB::table('users')->where('id', $user->id)->update($updates);
        }

        DB::table('projects')->whereNull('signed_by_user_id')->update(['signed_by_user_id' => $user->id]);
    }

    /** "KEP-324/KS.13/2026 tanggal 22 Mei 2026" -> ["KEP-324/KS.13/2026", "2026-05-22"]. */
    private static function split(string $value): array
    {
        $value = trim($value);
        if (! preg_match('/^(.*?)\s+tanggal\s+(\d{1,2})\s+([a-z]+)\s+(\d{4})$/i', $value, $m)) {
            return [$value !== '' ? $value : null, null];
        }
        $month = self::MONTHS[strtolower($m[3])] ?? null;

        return $month
            ? [trim($m[1]), sprintf('%04d-%02d-%02d', $m[4], $month, $m[2])]
            : [$value, null];
    }

    public function down(): void
    {
        // Data biodata & penugasan tidak dikembalikan — tidak bisa dibedakan
        // mana yang diisi migrasi ini dan mana yang diisi user setelahnya.
    }
};
