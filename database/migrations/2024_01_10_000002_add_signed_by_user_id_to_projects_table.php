<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penandatangan proposal — user dengan jabatan "Penanggung Jawab".
     * Blok tanda tangan .docx/PDF proposal mengambil nama + nomor izin
     * (MAPPI, RMK, Izin Menkeu, STTD OJK, Klasifikasi) dari biodata user
     * ini. nullOnDelete: kalau akunnya dihapus, proposal lama tidak error —
     * builder otomatis jatuh ke config('kjpp.signatory').
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('signed_by_user_id')->nullable()->after('assigned_appraiser_id')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signed_by_user_id');
        });
    }
};
