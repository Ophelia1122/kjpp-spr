<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanggal untuk SK Menkeu, Surat Tanda Terdaftar OJK, dan KEP OJK
     * (2026-09-15, feedback user) — nomor & tanggal surat saling mengikat,
     * jadi dicatat berpasangan. Sebelumnya tanggal diketik menempel di kolom
     * nomor ("185/MK/SJ/2025 tanggal 23 April 2025"); isian lama berformat itu
     * dipecah otomatis ke kolom tanggal yang baru.
     */
    private const PAIRS = [
        'sk_menkeu_no' => 'sk_menkeu_date',
        'sttd_ojk_no'  => 'sttd_ojk_date',
        'ojk_kep_no'   => 'ojk_kep_date',
    ];

    private const MONTHS = [
        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6,
        'juli' => 7, 'agustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (self::PAIRS as $no => $date) {
                $table->date($date)->nullable()->after($no);
            }
        });

        foreach (DB::table('users')->get() as $user) {
            $updates = [];
            foreach (self::PAIRS as $no => $date) {
                [$number, $parsed] = self::split((string) $user->{$no});
                if ($parsed) {
                    $updates[$no]   = $number;
                    $updates[$date] = $parsed;
                }
            }
            if ($updates) {
                DB::table('users')->where('id', $user->id)->update($updates);
            }
        }
    }

    /** "185/MK/SJ/2025 tanggal 23 April 2025" -> ["185/MK/SJ/2025", "2025-04-23"]. */
    private static function split(string $value): array
    {
        if (! preg_match('/^(.*?)\s+tanggal\s+(\d{1,2})\s+([a-z]+)\s+(\d{4})\s*$/i', trim($value), $m)) {
            return [$value, null];
        }
        $month = self::MONTHS[strtolower($m[3])] ?? null;

        return $month
            ? [trim($m[1]), sprintf('%04d-%02d-%02d', $m[4], $month, $m[2])]
            : [$value, null];
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(array_values(self::PAIRS));
        });
    }
};
