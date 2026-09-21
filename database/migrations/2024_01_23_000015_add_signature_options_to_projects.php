<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pilihan tanda tangan proposal (2026-09-21): barcode tanda tangan
 * (unggah per proposal) dan stempel kantor. Hanya MENAMBAH kolom; proposal
 * lama = tanpa barcode & tanpa stempel, jadi tampilannya tidak berubah.
 * representative_limited: Surat Representasi memuat poin inspeksi terbatas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('use_signature_barcode')->default(false)->after('signed_by_user_id');
            $table->string('signature_barcode')->nullable()->after('use_signature_barcode');
            $table->boolean('use_stamp')->default(false)->after('signature_barcode');
            $table->boolean('representative_limited')->default(false)->after('use_stamp');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['use_signature_barcode', 'signature_barcode', 'use_stamp', 'representative_limited']);
        });
    }
};
