<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alur akhir proyek & tanggal survei per objek (2026-09-23, feedback user).
 * Hanya MENAMBAH kolom; data lama disalin, tidak ada yang dihapus.
 *
 * - project_valuation_objects: survey_start_date & survey_end_date (boleh
 *   beda per objek, bisa lebih dari satu hari; end kosong = survei 1 hari).
 * - projects: signed_at & delivered_at (tahap tanda tangan & pengiriman buku)
 *   dan valuation_date_manual (Tanggal Penilaian boleh diisi manual).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_valuation_objects', function (Blueprint $table) {
            $table->date('survey_start_date')->nullable()->after('notes');
            $table->date('survey_end_date')->nullable()->after('survey_start_date');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->timestamp('signed_at')->nullable()->after('printed_at');
            $table->timestamp('delivered_at')->nullable()->after('signed_at');
            $table->date('valuation_date_manual')->nullable()->after('survey_date');
        });

        // Objek proyek lama: pakai tanggal survei proyek sebagai tanggal mulai.
        DB::statement('UPDATE project_valuation_objects o
            JOIN projects p ON p.id = o.project_id
            SET o.survey_start_date = p.survey_date
            WHERE o.survey_start_date IS NULL AND p.survey_date IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('project_valuation_objects', function (Blueprint $table) {
            $table->dropColumn(['survey_start_date', 'survey_end_date']);
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['signed_at', 'delivered_at', 'valuation_date_manual']);
        });
    }
};
