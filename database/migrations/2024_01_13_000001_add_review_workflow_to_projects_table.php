<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alur review 3-tahap sebelum SLA Laporan Final mulai dihitung:
 *
 *   1. Surveyor input tanggal survei (sudah ada, survey_date)
 *      -> SLA Draft/Resume mulai dihitung (sudah ada, estimated_completion_date).
 *   2. Surveyor "Submit untuk Review"      -> review_status = submitted
 *   3. Reviewer (jabatan = Reviewer)       -> review_status = reviewed
 *      (atau REJECT balik ke tahap 2: review_status = null lagi)
 *   4. Admin Produksi "Konfirmasi Disetujui" -> review_status = approved
 *      (atau REJECT balik ke tahap 3: review_status = submitted lagi)
 *      -> review_approved_at JADI TITIK MULAI SLA Laporan Final.
 *
 * SENGAJA terpisah dari status proyek utama (Project::STATUS_*) dan dari
 * alur Invoice Pelunasan — proses ini murni menentukan kapan SLA Final
 * mulai dihitung, tidak menahan/menunggu tombol "Tandai Draf Selesai".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('review_status', 20)->nullable()->after('final_report_number');

            $table->timestamp('review_submitted_at')->nullable()->after('review_status');
            $table->foreignId('review_submitted_by_user_id')->nullable()->after('review_submitted_at')
                ->constrained('users')->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable()->after('review_submitted_by_user_id');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('reviewed_at')
                ->constrained('users')->nullOnDelete();

            $table->timestamp('review_approved_at')->nullable()->after('reviewed_by_user_id');
            $table->foreignId('review_approved_by_user_id')->nullable()->after('review_approved_at')
                ->constrained('users')->nullOnDelete();

            // Penolakan (opsional) — dicatat di sini agar tampil di UI;
            // riwayat lengkap tetap ada di Log Aktivitas (audit_logs).
            $table->timestamp('review_rejected_at')->nullable()->after('review_approved_by_user_id');
            $table->foreignId('review_rejected_by_user_id')->nullable()->after('review_rejected_at')
                ->constrained('users')->nullOnDelete();
            $table->text('review_rejection_note')->nullable()->after('review_rejected_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('review_submitted_by_user_id');
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropConstrainedForeignId('review_approved_by_user_id');
            $table->dropConstrainedForeignId('review_rejected_by_user_id');
            $table->dropColumn([
                'review_status', 'review_submitted_at', 'reviewed_at',
                'review_approved_at', 'review_rejected_at', 'review_rejection_note',
            ]);
        });
    }
};
