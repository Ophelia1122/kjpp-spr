<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Model invoice diubah dari "selalu tepat 2 tahap (DP + Pelunasan)" jadi
 * BEBAS berapa kali terbit (termin fleksibel) — sistem hanya menjumlah
 * total yang sudah Paid vs total_fee proyek utk menentukan sisa tagihan.
 *
 *  - percentage    : persentase thd total_fee proyek SAAT invoice ini
 *                    dibuat (murni informasi/riwayat — nominal `amount`
 *                    tetap sumber kebenaran, supaya aman kalau fee proyek
 *                    berubah belakangan).
 *  - kwitansi_number : nomor kwitansi TERPISAH dari nomor invoice (format
 *                    beda: KJPPSPR-KEU-JK vs KJPPSPR-INV-JKT), diisi
 *                    otomatis saat invoice ditandai Paid (kwitansi cuma
 *                    terbit setelah uang benar diterima).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('percentage', 6, 2)->nullable()->after('amount');
            $table->string('kwitansi_number')->nullable()->unique()->after('invoice_number');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['percentage', 'kwitansi_number']);
        });
    }
};
