<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nama pihak yang MENYETUJUI proposal (blok tanda tangan kolom kanan).
     * Bisa bank, bisa klien (PT) — tergantung kasus — jadi diinput manual
     * per proposal. Kosong = otomatis pakai nama Pemberi Tugas.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('approver_name')->nullable()->after('signed_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('approver_name');
        });
    }
};
