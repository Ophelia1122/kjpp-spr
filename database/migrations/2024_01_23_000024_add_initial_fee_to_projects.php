<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nilai penawaran AWAL disimpan terpisah (2026-09-25, feedback user): klien
 * sering menawar setelah proposal dikirim, dan kantor perlu melihat selisih
 * antara yang ditawarkan dan yang akhirnya disepakati tanpa membuka riwayat.
 *
 * Proyek lama diisi dengan nilai yang berlaku sekarang, sehingga selisihnya
 * nol dan tidak ada yang terlihat berubah di layar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('initial_service_fee', 15, 2)->nullable()->after('service_fee');
            $table->decimal('initial_transport_cost', 15, 2)->nullable()->after('transport_cost');
        });

        DB::table('projects')->update([
            'initial_service_fee'    => DB::raw('service_fee'),
            'initial_transport_cost' => DB::raw('transport_cost'),
        ]);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['initial_service_fee', 'initial_transport_cost']);
        });
    }
};
