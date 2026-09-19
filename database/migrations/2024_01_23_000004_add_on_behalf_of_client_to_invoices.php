<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Biaya Jasa Penilaian Properti an. …" dipilih per invoice (2026-09-14,
 * feedback user) — pilihannya sama dengan "Telah diterima dari" (Pemberi
 * Tugas / Nama Klien / Pengguna Laporan). Kosong = Pemberi Tugas (data lama).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('on_behalf_of_client_id')->nullable()->after('received_from_client_id')
                ->constrained('clients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('on_behalf_of_client_id');
        });
    }
};
