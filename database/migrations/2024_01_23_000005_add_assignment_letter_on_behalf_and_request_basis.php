<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Surat Tugas (2026-09-14, feedback user):
 *  - assignment_letter_on_behalf_client_id : "Penilaian Aset atas nama …",
 *    pilihannya sama dengan invoice (Pemberi Tugas / Nama Klien / Pengguna
 *    Laporan). Kosong = Pemberi Tugas (Surat Tugas lama tidak berubah).
 *  - assignment_letter_request_basis : Dasar Permintaan Penilaian yang dicetak
 *    setelah nama klien. Kosong = memakai Dasar Permintaan di Identitas Proposal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('assignment_letter_on_behalf_client_id')->nullable()->after('assignment_letter_date')
                ->constrained('clients')->nullOnDelete();
            $table->text('assignment_letter_request_basis')->nullable()->after('assignment_letter_on_behalf_client_id');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assignment_letter_on_behalf_client_id');
            $table->dropColumn('assignment_letter_request_basis');
        });
    }
};
