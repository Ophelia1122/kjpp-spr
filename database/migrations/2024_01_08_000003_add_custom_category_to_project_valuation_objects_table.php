<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kalau kategori objek = "Lainnya", admin mengetik sendiri jenis
     * aset/propertinya di sini (mis. "Kapal", "Hak Sewa", "Tanaman
     * Keras", "Pesawat"). Dipakai menggantikan literal "Lainnya" saat
     * dirender di tabel Identifikasi Obyek Penilaian pada proposal.
     */
    public function up(): void
    {
        Schema::table('project_valuation_objects', function (Blueprint $table) {
            $table->string('custom_category')->nullable()->after('asset_category');
        });
    }

    public function down(): void
    {
        Schema::table('project_valuation_objects', function (Blueprint $table) {
            $table->dropColumn('custom_category');
        });
    }
};
