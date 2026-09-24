<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penilai lapangan per OBJEK penilaian (2026-09-25, feedback user). Sebelumnya
 * penilai hanya dicatat per proyek, sehingga SPJ Surveyor menghitung seluruh
 * objek untuk setiap penilai. Sekarang staf bisa memilih siapa yang turun ke
 * objek mana; tiap nama yang terpilih tetap mendapat SPJ penuh untuk objek itu.
 *
 * Objek TANPA baris di sini berarti "semua penilai proyek ikut" — itu bawaannya,
 * sekaligus membuat data lama tetap terbaca seperti semula.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_object_appraisers')) {
            return;
        }

        Schema::create('project_object_appraisers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->constrained('project_valuation_objects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['object_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_object_appraisers');
    }
};
