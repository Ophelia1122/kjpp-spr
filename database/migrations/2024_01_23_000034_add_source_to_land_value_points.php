<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Titik nilai tanah kini berasal dari dua sumber (2026-09-27, permintaan user):
 *   - aset      : objek penilaian KJPP (kesimpulan penilaian terdahulu)
 *   - pembanding: data penawaran/transaksi pasar yang disurvei
 *
 * Keduanya harus bisa dibedakan di peta, jadi jenisnya disimpan eksplisit
 * beserta keterangan sumbernya (nama, telepon, status) untuk data pembanding.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('land_value_points', function (Blueprint $table) {
            $table->string('data_type', 12)->default('aset')->after('id');
            $table->string('offer_type', 20)->nullable()->after('data_type');   // Penawaran / Transaksi
            $table->string('source_name', 120)->nullable()->after('source_file');
            $table->string('source_phone', 40)->nullable()->after('source_name');
            $table->string('source_status', 40)->nullable()->after('source_phone');

            $table->index(['data_type', 'property_group']);
        });

        // Baris yang sudah ada seluruhnya berasal dari data aset.
        DB::table('land_value_points')->update(['data_type' => 'aset']);
    }

    public function down(): void
    {
        Schema::table('land_value_points', function (Blueprint $table) {
            $table->dropIndex(['data_type', 'property_group']);
            $table->dropColumn(['data_type', 'offer_type', 'source_name', 'source_phone', 'source_status']);
        });
    }
};
