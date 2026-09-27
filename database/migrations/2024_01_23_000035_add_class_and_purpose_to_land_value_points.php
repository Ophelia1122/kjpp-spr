<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penyesuaian setelah data pembanding pasar masuk (2026-09-27, permintaan user):
 *
 *  - property_class : tiga kategori kerja — tanah_bangunan / ruko / apart_os_kios.
 *  - rate_basis     : satuan land_rate. Ruko & Apart/OS/Kios dihitung per m²
 *                     BANGUNAN, bukan tanah, jadi satuannya harus ikut tercatat
 *                     supaya dua satuan tidak pernah dijumlahkan jadi satu.
 *  - offer_total    : nilai penawaran/transaksi total, dipakai ekspor Excel.
 *  - purpose        : tujuan penilaian dari laporan induk; kosong = tidak diketahui.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('land_value_points', function (Blueprint $table) {
            $table->string('property_class', 20)->default('tanah_bangunan')->after('property_group');
            $table->string('rate_basis', 10)->default('tanah')->after('property_class');
            $table->unsignedBigInteger('offer_total')->nullable()->after('land_rate');
            $table->string('purpose', 60)->nullable()->after('offer_type');

            $table->index(['property_class', 'rate_basis']);
            $table->index('purpose');
        });
    }

    public function down(): void
    {
        Schema::table('land_value_points', function (Blueprint $table) {
            $table->dropIndex(['property_class', 'rate_basis']);
            $table->dropIndex(['purpose']);
            $table->dropColumn(['property_class', 'rate_basis', 'offer_total', 'purpose']);
        });
    }
};
