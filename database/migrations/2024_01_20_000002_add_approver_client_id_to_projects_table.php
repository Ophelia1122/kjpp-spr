<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Pihak yang Menyetujui" kini dipilih dari Database Klien (sama seperti
     * Pemberi Tugas & Pengguna Laporan), bukan lagi ketik bebas. Kolom lama
     * approver_name dibiarkan sebagai cadangan data lama — saat migrasi ini
     * dibuat belum ada satu proyek pun yang mengisinya.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('approver_client_id')->nullable()->after('approver_name')
                ->constrained('clients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approver_client_id');
        });
    }
};
