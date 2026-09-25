<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teks baku proposal per tujuan penilaian (2026-09-25, permintaan user).
 *
 * Satu baris = teks default SATU bab untuk SATU tujuan penilaian. Diisi dan
 * diubah dari menu Pengaturan Sistem > Teks Baku Proposal, sehingga perubahan
 * klausul tidak perlu menyentuh config/proposal_clauses.php.
 *
 * proposal_purpose = '' berarti berlaku untuk SEMUA tujuan penilaian.
 * Urutan pemakaian teks di ProposalDocxBuilder:
 *   override per proyek > default per tujuan > default semua tujuan > config.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_section_defaults', function (Blueprint $table) {
            $table->id();
            $table->string('proposal_purpose', 100)->default('');
            $table->string('section_key', 60);
            $table->text('body');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['proposal_purpose', 'section_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_section_defaults');
    }
};
