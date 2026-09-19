<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Surat Tugas "Kepada Yth" dipilih per proyek (2026-09-14, feedback user) —
 * pilihannya sama dengan "Penilaian Aset atas nama". Kosong = Pemberi Tugas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('assignment_letter_recipient_client_id')->nullable()->after('assignment_letter_date')
                ->constrained('clients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assignment_letter_recipient_client_id');
        });
    }
};
