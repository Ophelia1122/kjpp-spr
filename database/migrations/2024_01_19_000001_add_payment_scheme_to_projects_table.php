<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skema "Bayar Nanti" (2026-09-14, feedback user): sebagian klien baru
 * bayar di tengah/akhir pengerjaan, tanpa DP di muka. Kolom ini dipilih
 * SEKALI saat proposal masih Draft/Menunggu Persetujuan (lihat
 * ProposalController) dan menentukan apakah proyek boleh mulai kerja
 * lapangan lewat tombol "Mulai Pekerjaan (Tanpa DP)" tanpa invoice sama
 * sekali (lihat ProjectController::startWorkWithoutDp()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('payment_scheme')->default('DP di Awal')->after('proposal_purpose');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('payment_scheme');
        });
    }
};
