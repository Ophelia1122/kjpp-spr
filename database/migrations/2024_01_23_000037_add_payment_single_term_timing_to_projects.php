<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pilihan kapan termin dibayarkan untuk skema 1 tahap (100%), permintaan
 * admin 2026-10-02: defaultnya "sebelum laporan final diserahkan", tapi
 * boleh dipilih "sebelum inspeksi dilaksanakan" — sama seperti tahap
 * pertama pada skema 2/3 tahap. Kosong = default lama, tidak ada proyek
 * lama yang berubah kalimatnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('payment_single_term_timing', 20)->nullable()->after('payment_terms');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('payment_single_term_timing');
        });
    }
};
