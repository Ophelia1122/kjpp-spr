<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Surat Tugas (format resmi baru): Nomor & Tanggal diisi manual oleh
 * Administrator/Admin Keuangan (bukan digenerate otomatis), + Reviewer yang
 * dicetak di tabel "Adapun petugas kami" berdampingan dengan Penilai
 * Lapangan yang sudah ada lewat assigned_appraiser_id, + barcode verifikasi
 * (PNG, upload lewat kartu Surat Tugas di halaman proposal).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('reviewer_id')->nullable()->after('signed_by_user_id')
                  ->constrained('users')->nullOnDelete();

            $table->string('assignment_letter_number')->nullable()->after('tax_invoice_date');
            $table->date('assignment_letter_date')->nullable()->after('assignment_letter_number');
            $table->string('assignment_letter_barcode')->nullable()->after('assignment_letter_date');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewer_id');
            $table->dropColumn(['assignment_letter_number', 'assignment_letter_date', 'assignment_letter_barcode']);
        });
    }
};
