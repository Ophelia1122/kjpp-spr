<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom Kualifikasi pada daftar Petugas (2026-09-26, permintaan user).
 * Dipakai di tabel Tim Pelaksana pada proposal Jasa Konsultasi — kolomnya
 * Posisi, Nama, Kualifikasi. Keduanya diketik manual per petugas; namanya
 * tetap diambil dari Kelola Pengguna.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_assignment_staff', function (Blueprint $table) {
            $table->string('qualification', 120)->nullable()->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('project_assignment_staff', function (Blueprint $table) {
            $table->dropColumn('qualification');
        });
    }
};
