<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - client_name: "Nama Klien" (debitur/pemilik aset), terpisah dari Pemberi
     *   Tugas — kasus lelang/penjaminan utang sering Pemberi Tugas-nya bank.
     *   Kosong = pakai nama Pemberi Tugas. Dipakai di baris "Hal" proposal.
     * - transport_reimbursed: Transport & Akomodasi ditanggung klien
     *   (reimbursement) — tidak masuk total biaya, proposal diberi catatan.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('client_name')->nullable()->after('instructing_client_id');
            $table->boolean('transport_reimbursed')->default(false)->after('transport_cost');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['client_name', 'transport_reimbursed']);
        });
    }
};
