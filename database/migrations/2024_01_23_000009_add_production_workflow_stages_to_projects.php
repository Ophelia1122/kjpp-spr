<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alur produksi laporan (2026-09-15, feedback user) — kolom review_status kini
 * memuat tahap lengkap:
 *   submitted -> approved (nilai disetujui, SLA Final mulai) -> draft_submitted
 *   -> draft_confirmed -> draft_reviewed (proses cetak) -> printed (Selesai)
 *
 * Status "Pelunasan" dihapus: Selesai = buku selesai dicetak, pembayaran boleh
 * menyusul (lunas/belum lunas ditampilkan terpisah).
 *
 * Pemetaan data lama:
 *   reviewed (menunggu konfirmasi Admin Produksi) -> approved (SLA Final mulai saat Reviewer setuju)
 *   status Pelunasan                              -> In-Progress, tahap draft_reviewed (proses cetak)
 *   status Selesai                                -> tahap printed
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->timestamp('draft_submitted_at')->nullable()->after('review_rejection_note');
            $table->timestamp('draft_confirmed_at')->nullable()->after('draft_submitted_at');
            $table->timestamp('draft_reviewed_at')->nullable()->after('draft_confirmed_at');
            $table->timestamp('printed_at')->nullable()->after('draft_reviewed_at');
        });

        DB::table('projects')->where('review_status', 'reviewed')->update([
            'review_status'      => 'approved',
            'review_approved_at' => DB::raw('COALESCE(review_approved_at, reviewed_at)'),
        ]);

        DB::table('projects')->where('status', 'Pelunasan')->update([
            'status'            => 'In-Progress / Scheduled',
            'review_status'     => 'draft_reviewed',
            'draft_reviewed_at' => DB::raw('updated_at'),
        ]);

        DB::table('projects')->where('status_before_cancel', 'Pelunasan')->update([
            'status_before_cancel' => 'In-Progress / Scheduled',
            'review_status'        => 'draft_reviewed',
        ]);

        DB::table('projects')->where('status', 'Selesai')->update([
            'review_status' => 'printed',
            'printed_at'    => DB::raw('COALESCE(printed_at, updated_at)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['draft_submitted_at', 'draft_confirmed_at', 'draft_reviewed_at', 'printed_at']);
        });
    }
};
