<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * proposal_section_texts — OVERRIDE teks baku proposal per-bab, per-proyek.
 *
 * Hanya menyimpan baris untuk bab yang BENAR-BENAR diedit staf. Bab tanpa
 * baris di sini otomatis memakai teks baku dari config/proposal_clauses.php
 * (lihat App\Services\ProposalDocxBuilder::bodyOr()).
 *
 * `body` = teks hasil edit, sudah "ter-render" (placeholder seperti nama
 * klien / nominal biaya sudah diganti nilai nyata saat textarea diisi).
 * Karena itu override TIDAK ikut berubah kalau data proyek diubah — staf
 * bisa klik "Reset" untuk kembali ke teks baku terkini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_section_texts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('section_key', 64);
            $table->longText('body');
            $table->timestamps();

            $table->unique(['project_id', 'section_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_section_texts');
    }
};
