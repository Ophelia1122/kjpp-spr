<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectValuationObject extends Model
{
    public const CATEGORY_TANAH              = 'Real Properti - Tanah';
    public const CATEGORY_BANGUNAN           = 'Real Properti - Bangunan';
    public const CATEGORY_TANAH_BANGUNAN     = 'Real Properti - Tanah dan Bangunan';
    public const CATEGORY_MESIN              = 'Personal Properti - Mesin dan Peralatan';
    public const CATEGORY_KENDARAAN          = 'Personal Properti - Kendaraan';
    public const CATEGORY_ALAT_BERAT         = 'Personal Properti - Alat Berat';
    public const CATEGORY_BISNIS             = 'Bisnis / Perusahaan';
    public const CATEGORY_LAINNYA            = 'Lainnya';

    protected $fillable = [
        'project_id',
        'sort_order',
        'asset_category',
        'custom_category',
        'land_area',
        'building_area',
        'unit_quantity',
        'location',
        'ownership_form',
        'owner_name',
        'notes',
    ];

    protected $casts = [
        'land_area'     => 'decimal:2',
        'building_area' => 'decimal:2',
        'unit_quantity' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * True kalau kategori termasuk Real Properti (Tanah/Bangunan/keduanya)
     * -> field yang relevan adalah luas tanah & luas bangunan.
     */
    public function getIsRealPropertyAttribute(): bool
    {
        return str_starts_with($this->asset_category, 'Real Properti');
    }

    /**
     * True kalau kategori termasuk Personal Properti (Mesin/Kendaraan/
     * Alat Berat) -> field yang relevan adalah jumlah unit.
     */
    public function getIsPersonalPropertyAttribute(): bool
    {
        return str_starts_with($this->asset_category, 'Personal Properti');
    }

    /**
     * Menyusun deskripsi multi-baris untuk kolom "Jenis Aset/Properti"
     * di tabel PDF proposal, meniru format dokumen asli KJPP:
     * "Real Properti / Tanah, Bangunan dan Sarana Pelengkap /
     *  Luas Tanah: 72 m2 / Luas Bangunan: 42.80 m2"
     * Dikembalikan sebagai array baris supaya Blade tinggal @foreach
     * dan taruh <br> di antaranya (aman untuk DomPDF).
     */
    public function getDescriptionLinesAttribute(): array
    {
        $lines = [];

        if ($this->is_real_property) {
            $lines[] = 'Real Properti';
            $lines[] = str_replace('Real Properti - ', '', $this->asset_category)
                . ($this->notes ? " ({$this->notes})" : '');

            if ($this->land_area) {
                $lines[] = 'Luas Tanah: ' . number_format((float) $this->land_area, 2, ',', '.') . ' m2';
            }
            if ($this->building_area) {
                $lines[] = 'Luas Bangunan: ' . number_format((float) $this->building_area, 2, ',', '.') . ' m2';
            }
        } elseif ($this->is_personal_property) {
            $lines[] = 'Personal Properti';
            $categoryLabel = str_replace('Personal Properti - ', '', $this->asset_category);
            $lines[] = $categoryLabel . ($this->unit_quantity ? " sejumlah {$this->unit_quantity} unit" : '');
            if ($this->notes) {
                $lines[] = "({$this->notes})";
            }
        } else {
            // "Lainnya" -> pakai jenis yang diketik manual admin (custom_category);
            // "Bisnis / Perusahaan" -> custom_category null, jatuh ke asset_category.
            $lines[] = ($this->asset_category === self::CATEGORY_LAINNYA && $this->custom_category)
                ? $this->custom_category
                : $this->asset_category;
            if ($this->notes) {
                $lines[] = $this->notes;
            }
        }

        return $lines;
    }

    /**
     * Label ringkas 1 baris (dipakai di dashboard / summary, bukan PDF).
     */
    public function getShortLabelAttribute(): string
    {
        if ($this->asset_category === self::CATEGORY_LAINNYA && $this->custom_category) {
            return $this->custom_category;
        }

        return str_replace(['Real Properti - ', 'Personal Properti - '], '', $this->asset_category);
    }
}
