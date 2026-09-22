<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Biodata untuk bab "Penjelasan Status Penilai" versi poin (2026-09-22):
 * izin Penilai Pertanahan (opsional, tidak semua penilai punya) dan daftar
 * sektor jasa keuangan pada Surat Tanda Terdaftar OJK. Hanya menambah kolom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pertanahan_izin_no')->nullable()->after('sttd_ojk_date');
            $table->date('pertanahan_izin_date')->nullable()->after('pertanahan_izin_no');
            $table->json('ojk_sectors')->nullable()->after('pertanahan_izin_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pertanahan_izin_no', 'pertanahan_izin_date', 'ojk_sectors']);
        });
    }
};
