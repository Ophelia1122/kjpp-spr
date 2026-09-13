<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom "Nama Pemilik Aset (Umum)" dihapus. Kepemilikan sekarang
     * cukup dijabarkan per-objek di tabel project_valuation_objects
     * (kolom owner_name), sesuai format tabel "Identifikasi Obyek
     * Penilaian" pada dokumen resmi KJPP — tidak perlu lagi 1 field
     * "pemilik umum" di level proyek yang sering rancu dengan Pemberi
     * Tugas.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('property_owner_name');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('property_owner_name')->nullable()->after('instructing_client_id');
        });
    }
};
