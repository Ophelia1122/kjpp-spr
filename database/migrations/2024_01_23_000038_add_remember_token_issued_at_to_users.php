<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kapan cookie "Ingat saya di perangkat ini" diterbitkan (2026-10-02,
 * permintaan admin). Cookie remember bawaan Laravel bertahan 5 tahun dan
 * bikin login tidak pernah habis walau PC dimatikan — kolom ini dipakai
 * EnsureRememberNotExpired buat paksa login ulang kalau sudah lebih dari
 * 24 jam sejak login lewat cookie itu (lihat LoginController & middleware
 * terkait).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('remember_token_issued_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('remember_token_issued_at');
        });
    }
};
