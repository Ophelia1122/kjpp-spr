<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persentase termin pembayaran per proposal (2026-09-19, feedback user).
 * Kosong = ikut default skema: DP di Awal 50/50, Bayar Nanti 100%.
 * Isi berupa array persen, contoh [30, 70] atau [50, 30, 20].
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('payment_terms')->nullable()->after('payment_scheme');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('payment_terms');
        });
    }
};
