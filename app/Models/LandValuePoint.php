<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu titik nilai tanah dari penilaian terdahulu (2026-09-26, permintaan
 * user) — bahan estimasi rentang nilai pasar tanah per meter.
 *
 * CATATAN PENTING: ini nilai KESIMPULAN penilaian, bukan data penawaran
 * pasar. Dipakai sebagai acuan awal, bukan sebagai pembanding di laporan.
 */
class LandValuePoint extends Model
{
    protected $fillable = [
        'data_type', 'offer_type', 'purpose', 'source_name', 'source_phone', 'source_status',
        'property_class', 'rate_basis', 'offer_total', 'offer_land', 'offer_building',
        'report_number', 'valuation_date', 'valuation_year',
        'latitude', 'longitude',
        'property_type', 'property_group',
        'land_rate', 'building_rate', 'land_area', 'building_area',
        'province', 'city', 'district', 'village', 'address',
        'source_file',
    ];

    protected $casts = [
        'valuation_date' => 'date',
        'latitude'       => 'float',
        'longitude'      => 'float',
        'land_rate'      => 'integer',
        'offer_total'    => 'integer',
        'offer_land'     => 'integer',
        'offer_building' => 'integer',
        'building_rate'  => 'integer',
        'land_area'      => 'integer',
        'building_area'  => 'integer',
    ];

    /** Objek penilaian KJPP sendiri. */
    public const TIPE_ASET = 'aset';

    /** Data penawaran/transaksi pasar hasil survei. */
    public const TIPE_PEMBANDING = 'pembanding';

    public const TIPE_LABELS = [
        self::TIPE_ASET        => 'Objek penilaian',
        self::TIPE_PEMBANDING  => 'Data pembanding',
    ];

    public function getTipeLabelAttribute(): string
    {
        return self::TIPE_LABELS[$this->data_type] ?? $this->data_type;
    }

    /**
     * Tujuan penilaian ditulis bermacam gaya di berkas ("REVALUASI ASET",
     * "Revaluasi Aset"). Dibakukan supaya saringannya tidak pecah.
     */
    public static function tujuanBaku(?string $tujuan): ?string
    {
        $t = mb_strtolower(trim((string) $tujuan));

        if ($t === '' || $t === '-') {
            return null;
        }

        return match (true) {
            str_contains($t, 'penjaminan')                        => 'Penjaminan Utang',
            str_contains($t, 'lelang')                            => 'Lelang',
            str_contains($t, 'jual beli') || str_contains($t, 'dibeli') => 'Jual Beli',
            str_contains($t, 'laporan keuangan') || str_contains($t, 'akuntansi')
                || str_contains($t, 'pencatatan')                 => 'Laporan Keuangan',
            str_contains($t, 'revaluasi')                         => 'Revaluasi Aset',
            str_contains($t, 'ayda')                              => 'AYDA',
            str_contains($t, 'sewa')                              => 'Sewa',
            default                                               => 'Lainnya',
        };
    }

    /**
     * Tiga kategori kerja (2026-09-27, permintaan user). Ruko dan
     * Apart/OS/Kios dipisah karena harganya dihitung per m² BANGUNAN, bukan
     * per m² tanah — angkanya sama pentingnya, tapi satuannya beda dan tidak
     * boleh dijumlahkan jadi satu.
     */
    public const KELAS_TANAH_BANGUNAN = 'tanah_bangunan';
    public const KELAS_RUKO           = 'ruko';
    public const KELAS_APART          = 'apart_os_kios';

    public const KELAS_LABELS = [
        self::KELAS_TANAH_BANGUNAN => 'Tanah & Bangunan',
        self::KELAS_RUKO           => 'Ruko',
        self::KELAS_APART          => 'Apart / OS / Kios',
    ];

    /** Satuan land_rate per kelas. */
    public const SATUAN = [
        self::KELAS_TANAH_BANGUNAN => 'tanah',
        self::KELAS_RUKO           => 'bangunan',
        self::KELAS_APART          => 'bangunan',
    ];

    public const SATUAN_LABELS = [
        'tanah'    => 'per m² tanah',
        'bangunan' => 'per m² bangunan',
    ];

    /**
     * Jenis properti dari berkas ditulis ratusan ragam. Yang menentukan cara
     * hitungnya cuma tiga kategori di atas.
     */
    public static function kelas(?string $jenis): string
    {
        $j = mb_strtolower(trim((string) $jenis));

        return match (true) {
            str_contains($j, 'ruko') || str_contains($j, 'rukan')     => self::KELAS_RUKO,
            str_contains($j, 'apartemen') || str_contains($j, 'apart')
                || str_contains($j, 'office space') || str_contains($j, 'kios')
                || str_contains($j, 'per unit') || str_contains($j, 'condotel')
                || str_contains($j, 'strata')                          => self::KELAS_APART,
            default                                                    => self::KELAS_TANAH_BANGUNAN,
        };
    }

    public function getKelasLabelAttribute(): string
    {
        return self::KELAS_LABELS[$this->property_class] ?? $this->property_class;
    }

    public function getSatuanLabelAttribute(): string
    {
        return self::SATUAN_LABELS[$this->rate_basis] ?? $this->rate_basis;
    }

    /**
     * Kelompok properti untuk menyaring pembanding. Jenis Properti dari pusat
     * puluhan macam; yang menentukan harga tanah pada praktiknya adalah
     * peruntukannya, bukan nama persisnya.
     */
    public const GROUPS = [
        'tanah'     => 'Tanah kosong',
        'hunian'    => 'Hunian',
        'komersial' => 'Komersial',
        'industri'  => 'Industri / Pergudangan',
        'lain'      => 'Lainnya',
    ];

    /** Peta "Jenis Properti" dari berkas pusat ke kelompok di atas. */
    public static function kelompok(?string $jenis): string
    {
        $j = mb_strtolower(trim((string) $jenis));

        return match (true) {
            $j === 'tanah' || str_starts_with($j, 'tanah kosong')       => 'tanah',
            str_contains($j, 'rumah') || str_contains($j, 'apartemen')
                || str_contains($j, 'kost') || str_contains($j, 'villa')
                || str_contains($j, 'vila')                            => 'hunian',
            str_contains($j, 'ruko') || str_contains($j, 'rukan')
                || str_contains($j, 'kantor') || str_contains($j, 'toko')
                || str_contains($j, 'kios') || str_contains($j, 'hotel')
                || str_contains($j, 'mall') || str_contains($j, 'restoran')
                || str_contains($j, 'sekolah') || str_contains($j, 'klinik')
                || str_contains($j, 'rumah sakit')                     => 'komersial',
            str_contains($j, 'gudang') || str_contains($j, 'pabrik')
                || str_contains($j, 'industri') || str_contains($j, 'workshop')
                || str_contains($j, 'bengkel')                         => 'industri',
            default                                                    => 'lain',
        };
    }

    public function getGroupLabelAttribute(): string
    {
        return self::GROUPS[$this->property_group] ?? 'Lainnya';
    }
}
