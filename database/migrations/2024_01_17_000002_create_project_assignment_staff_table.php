<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar petugas yang dicetak di tabel "Adapun petugas kami" pada Surat
 * Tugas — jumlah & jabatan bebas per proyek (mis. 2 Penilai + 1 Reviewer,
 * atau 1 Reviewer + 1 Penilai + 1 Pelaksana Inspeksi), bukan selalu 1
 * Reviewer + 1 Penilai Lapangan seperti asumsi awal. Nama/Jabatan/No.
 * MAPPI yang tercetak diambil dari biodata `users` (jabatan sudah ada:
 * Reviewer/Penilai/Pelaksana Inspeksi/dst) — tabel ini murni "siapa saja
 * yang ditambahkan ke daftar petugas proyek ini", tanpa field jabatan
 * sendiri supaya tidak dobel sumber data dengan biodata user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_assignment_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_assignment_staff');
    }
};
