<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan aplikasi sederhana (key-value). Dipakai pertama kali untuk
 * "nomor terakhir" Invoice & Kwitansi yang terbit di luar aplikasi sebelum
 * aplikasi mulai dipakai (2026-09-14, feedback user) — lihat DocumentNumbering.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
