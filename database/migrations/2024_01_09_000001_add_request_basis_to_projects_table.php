<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Dasar Permintaan Penilaian" — bagian variabel pada kalimat pembuka
     * proposal ("Sesuai dengan informasi permintaan penilaian ___ mengenai
     * permohonan jasa Penilai ..."). Diinput MANUAL karena bentuknya
     * bermacam-macam (email / Pesan WhatsApp / surat permintaan / surat
     * order, masing-masing dengan nomor & tanggal berbeda).
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('request_basis')->nullable()->after('proposal_number');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('request_basis');
        });
    }
};
