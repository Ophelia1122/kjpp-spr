<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Feature 4 — Pisahkan Jenis Laporan + SLA manual:
     *
     * 1. report_style: enum lama 'Terinci'/'Ringkas' diganti menjadi
     *    'Long Report'/'Short Report' (istilah yang dipakai tim).
     *    'Long Report'  = Laporan Terinci (Comprehensive Style Report)
     *    'Short Report' = Laporan Ringkas (Short Form Report)
     *
     * 2. SLA tidak lagi hardcode 7/3 hari. Dokumen resmi memuat DUA
     *    jangka waktu terpisah yang wajib diisi manual (hari kerja):
     *    - sla_draft_days : Laporan Draft/Resume, dihitung sejak inspeksi
     *                       terakhir & penerimaan data terakhir.
     *    - sla_final_days : Laporan Final, dihitung sejak draft/resume
     *                       disetujui Pemberi Tugas.
     */
    public function up(): void
    {
        // Longgarkan dulu jadi VARCHAR supaya nilai baru tidak "truncated"
        // oleh definisi enum lama saat konversi data.
        DB::statement("ALTER TABLE projects MODIFY COLUMN report_style VARCHAR(20) NOT NULL");

        DB::table('projects')->where('report_style', 'Terinci')->update(['report_style' => 'Long Report']);
        DB::table('projects')->where('report_style', 'Ringkas')->update(['report_style' => 'Short Report']);

        DB::statement("ALTER TABLE projects MODIFY COLUMN report_style ENUM('Long Report', 'Short Report') NOT NULL DEFAULT 'Long Report'");

        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedSmallInteger('sla_draft_days')->nullable()->after('report_style');
            $table->unsignedSmallInteger('sla_final_days')->nullable()->after('sla_draft_days');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['sla_draft_days', 'sla_final_days']);
        });

        DB::statement("ALTER TABLE projects MODIFY COLUMN report_style VARCHAR(20) NOT NULL");

        DB::table('projects')->where('report_style', 'Long Report')->update(['report_style' => 'Terinci']);
        DB::table('projects')->where('report_style', 'Short Report')->update(['report_style' => 'Ringkas']);

        DB::statement("ALTER TABLE projects MODIFY COLUMN report_style ENUM('Terinci', 'Ringkas') NOT NULL");
    }
};
