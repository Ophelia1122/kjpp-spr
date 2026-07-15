<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Menentukan teks Dasar Nilai, Maksud Penilaian, dan klausul
            // kondisional mana yang dipakai di PDF proposal.
            $table->enum('proposal_purpose', [
                'Jual Beli',
                'Penjaminan Utang',
                'Lelang',
                'Pelaporan Keuangan',
            ])->default('Jual Beli')->after('report_style');

            // Khusus proposal_purpose = 'Pelaporan Keuangan'
            $table->string('psak_classification')->nullable()->after('proposal_purpose');
            // Contoh isi: "Aset Tetap (PSAK 16), Persediaan (PSAK 14)"
            // Disimpan sebagai string bebas (bukan enum) karena PSAK bisa
            // dipilih lebih dari satu sekaligus (lihat komentar Tedy Rachman
            // di dokumen asli: "bisa memilih lebih dari 1").

            $table->date('financial_reporting_date')->nullable()->after('psak_classification');
            // Tanggal penilaian untuk LK Properti = tanggal cut-off laporan
            // keuangan, BUKAN survey_date. Dua tanggal ini sengaja dipisah
            // karena secara bisnis maknanya berbeda (lihat komentar c16 di
            // dokumen 004_LK_PROPERTI: "tanyakan cut off penilaian untuk
            // periode kapan").

            $table->boolean('is_public_company')->default(false)->after('financial_reporting_date');
            // Mengontrol apakah klausul POJK 28/POJK.04/2021 dimunculkan
            // di teks Dasar Nilai (hanya berlaku untuk perusahaan terbuka).
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'proposal_purpose',
                'psak_classification',
                'financial_reporting_date',
                'is_public_company',
            ]);
        });
    }
};
