<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * reviewer_id (single FK) diganti oleh tabel project_assignment_staff —
 * ternyata petugas yang dicetak di Surat Tugas jumlah & komposisinya
 * bervariasi per proyek (mis. 2 Penilai + 1 Reviewer, atau 1 Reviewer +
 * 1 Penilai + 1 Pelaksana Inspeksi), bukan selalu tepat "1 Reviewer".
 * Belum pernah dipakai di luar migration sebelumnya (belum di-commit),
 * jadi aman di-drop langsung tanpa migrasi data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewer_id');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('reviewer_id')->nullable()->after('signed_by_user_id')
                  ->constrained('users')->nullOnDelete();
        });
    }
};
