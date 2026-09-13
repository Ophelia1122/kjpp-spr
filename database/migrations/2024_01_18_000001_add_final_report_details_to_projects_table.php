<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Melengkapi Nomor Laporan Resmi (STATUS_SELESAI) dengan Tanggal Final &
 * Keterangan opsional (2026-09-13, feedback user).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->date('final_report_date')->nullable()->after('final_report_number');
            $table->text('final_report_notes')->nullable()->after('final_report_date');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['final_report_date', 'final_report_notes']);
        });
    }
};
