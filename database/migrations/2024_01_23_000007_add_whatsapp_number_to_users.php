<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nomor WhatsApp pengguna (2026-09-14, feedback user) — dipakai bot notifikasi
 * untuk me-mention Reviewer/Surveyor di grup WhatsApp kantor. Disimpan dalam
 * format internasional tanpa "+" (mis. 6281234567890).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp_number', 20)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('whatsapp_number');
        });
    }
};
