<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penyesuaian kategori aset objek penilaian (2026-09-15, feedback user):
     *   "Real Properti - Tanah dan Bangunan" -> "Real Properti - Tanah, Bangunan dan Sarana Pelengkap"
     *   "Real Properti - Bangunan"           -> "Real Properti - Tanah dan Bangunan"
     *   "Bisnis / Perusahaan"                -> dihapus dari pilihan ("Lainnya" tetap)
     *
     * Kolom diubah dari ENUM ke VARCHAR supaya daftar kategori cukup diatur di
     * aplikasi, tanpa ALTER TABLE tiap kali ada perubahan pilihan.
     * Urutan update PENTING: ganti "Tanah dan Bangunan" dulu, baru "Bangunan",
     * kalau tidak data "Bangunan" yang baru diganti ikut tergeser dua kali.
     */
    public function up(): void
    {
        Schema::table('project_valuation_objects', function (Blueprint $table) {
            $table->string('asset_category')->change();
        });

        DB::table('project_valuation_objects')
            ->where('asset_category', 'Real Properti - Tanah dan Bangunan')
            ->update(['asset_category' => 'Real Properti - Tanah, Bangunan dan Sarana Pelengkap']);

        DB::table('project_valuation_objects')
            ->where('asset_category', 'Real Properti - Bangunan')
            ->update(['asset_category' => 'Real Properti - Tanah dan Bangunan']);
    }

    public function down(): void
    {
        DB::table('project_valuation_objects')
            ->where('asset_category', 'Real Properti - Tanah dan Bangunan')
            ->update(['asset_category' => 'Real Properti - Bangunan']);

        DB::table('project_valuation_objects')
            ->where('asset_category', 'Real Properti - Tanah, Bangunan dan Sarana Pelengkap')
            ->update(['asset_category' => 'Real Properti - Tanah dan Bangunan']);
    }
};
