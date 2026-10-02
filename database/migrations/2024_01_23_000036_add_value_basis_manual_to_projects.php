<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom Dasar Nilai manual untuk proposal Penilaian (2026-10-02, permintaan
 * user). Sebelumnya Dasar Nilai selalu diturunkan dari tujuan penilaian
 * (Nilai Pasar / Nilai Wajar). Kolom ini dipakai bila penilai perlu
 * menuliskannya sendiri; kosong = tetap otomatis seperti sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('value_basis_manual', 120)->nullable()->after('proposal_purpose');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('value_basis_manual');
        });
    }
};
