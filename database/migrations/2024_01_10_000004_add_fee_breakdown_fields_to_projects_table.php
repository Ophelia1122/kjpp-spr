<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rincian biaya jasa penilaian.
     *
     * - fee_ppn_included : apakah `service_fee` (nilai dasar) sudah termasuk
     *   PPN. Kalau belum, angka final = service_fee + PPN.
     * - fee_breakdown    : tampilkan "Rincian Biaya" (Fee/Transport/PPN/Total)
     *   di proposal, atau cukup angka all-in.
     * - transport_cost   : komponen Transport + Akomodasi (mode rincian).
     *
     * Default menjaga perilaku lama: sudah termasuk PPN, all-in, tanpa
     * transport → total = service_fee.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('fee_ppn_included')->default(true)->after('service_fee');
            $table->boolean('fee_breakdown')->default(false)->after('fee_ppn_included');
            $table->decimal('transport_cost', 15, 2)->nullable()->after('fee_breakdown');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['fee_ppn_included', 'fee_breakdown', 'transport_cost']);
        });
    }
};
