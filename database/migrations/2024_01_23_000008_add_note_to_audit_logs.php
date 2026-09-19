<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan opsional per langkah alur proyek (2026-09-14, feedback user) — mis.
 * catatan Surveyor saat Submit Review, catatan Reviewer, alasan pengembalian.
 * Ditampilkan di Riwayat Proyek pada kartu "Catatan & Langkah Berikutnya".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->text('note')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
