<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jenis layanan (2026-09-25, permintaan user). Sampai sekarang setiap proyek
 * selalu berupa Penilaian. Kantor mulai menerima pekerjaan Jasa Konsultasi
 * (SPI 350): Kajian Kewajaran RAB, Studi Kelayakan, dan Pengawasan Proyek.
 *
 * - service_type    : 'Penilaian' (bawaan, seluruh data lama) atau 'Konsultasi'
 * - consulting_type : jenis pekerjaan konsultasinya, null untuk Penilaian
 * - work_object_description : uraian objek pekerjaan berupa satu paragraf,
 *   dipakai di bab Objek Pekerjaan dan di Surat Tugas. Proposal penilaian
 *   tetap memakai tabel objek seperti biasa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('service_type', 30)->default('Penilaian')->after('proposal_date');
            $table->string('consulting_type', 60)->nullable()->after('service_type');
            $table->text('work_object_description')->nullable()->after('asset_address');

            $table->index('service_type');
        });

        // Posisi pada Surat Tugas & tabel Tim Pelaksana, diketik manual
        // ("Reviewer", "Tim Inspeksi", "Analyst dan Penyusun", ...). Kalau
        // kosong, Surat Tugas tetap memakai Jabatan dari biodata pengguna.
        Schema::table('project_assignment_staff', function (Blueprint $table) {
            $table->string('position', 100)->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['service_type']);
            $table->dropColumn(['service_type', 'consulting_type', 'work_object_description']);
        });

        Schema::table('project_assignment_staff', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
