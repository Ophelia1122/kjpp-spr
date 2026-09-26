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
        'report_number', 'valuation_date', 'valuation_year',
        'latitude', 'longitude',
        'property_type', 'property_group',
        'land_rate', 'land_area', 'building_area',
        'province', 'city', 'district', 'village', 'address',
        'source_file',
    ];

    protected $casts = [
        'valuation_date' => 'date',
        'latitude'       => 'float',
        'longitude'      => 'float',
        'land_rate'      => 'integer',
        'land_area'      => 'integer',
        'building_area'  => 'integer',
    ];

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
