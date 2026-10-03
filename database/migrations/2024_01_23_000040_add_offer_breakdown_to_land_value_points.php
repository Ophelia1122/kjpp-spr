<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rincian nilai penawaran pembanding (2026-10-03, permintaan user): total
 * sudah ada (offer_total); sekarang ditambah bagian tanah, bagian bangunan,
 * dan nilai per m² bangunan, supaya detail titik di Map Market menampilkan
 * angka seperti di berkas sumber. Terisi saat impor ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('land_value_points', function (Blueprint $table) {
            $table->unsignedBigInteger('offer_land')->nullable()->after('offer_total');
            $table->unsignedBigInteger('offer_building')->nullable()->after('offer_land');
            $table->unsignedBigInteger('building_rate')->nullable()->after('land_rate');
        });
    }

    public function down(): void
    {
        Schema::table('land_value_points', function (Blueprint $table) {
            $table->dropColumn(['offer_land', 'offer_building', 'building_rate']);
        });
    }
};
