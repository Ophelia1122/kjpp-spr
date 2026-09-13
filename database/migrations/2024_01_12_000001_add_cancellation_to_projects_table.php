<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status "Batal" (Batch 7). Proyek yang dibatalkan TIDAK dihapus — hanya
 * ganti status. Supaya bisa "diaktifkan kembali" ke tahap yang benar,
 * status terakhir sebelum dibatalkan disimpan di status_before_cancel.
 * cancelled_at dipakai untuk keterangan di halaman detail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('status_before_cancel', 64)->nullable()->after('status');
            $table->timestamp('cancelled_at')->nullable()->after('status_before_cancel');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['status_before_cancel', 'cancelled_at']);
        });
    }
};
