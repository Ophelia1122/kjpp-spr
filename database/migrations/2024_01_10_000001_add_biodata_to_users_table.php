<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Biodata profesi per pengguna. Dipakai untuk mengisi BLOK TANDA TANGAN
     * proposal ketika pengguna tsb dipilih sebagai penandatangan
     * (jabatan = "Penanggung Jawab"). Semua nullable — hanya relevan untuk
     * user yang memang Penilai/Penanggung Jawab; Admin/Surveyor boleh kosong.
     * Kalau kosong, ProposalDocxBuilder jatuh ke config('kjpp.signatory').
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Klasifikasi internal (dipakai untuk memfilter siapa yang boleh
            // jadi penandatangan proposal).
            $table->string('jabatan')->nullable()->after('is_active');
            // Status/jabatan di dalam perusahaan (mis. "Partner" /
            // "Managing Partner") — beda per orang, TERCETAK sebagai jabatan
            // di blok tanda tangan proposal. Bukan status keprofesian.
            $table->string('partner_status')->nullable()->after('jabatan');
            $table->string('mappi_no')->nullable()->after('partner_status');
            $table->string('rmk_no')->nullable()->after('mappi_no');
            // Izin Penilai Publik / Izin Menkeu (No. :izin di Penjelasan Status Penilai).
            $table->string('izin_menkeu_no')->nullable()->after('rmk_no');
            // SK Menteri Keuangan RI pengangkatan Penilai Publik (No. :sk_menkeu).
            $table->string('sk_menkeu_no')->nullable()->after('izin_menkeu_no');
            // Surat Tanda Terdaftar OJK (dicetak di blok tanda tangan).
            $table->string('sttd_ojk_no')->nullable()->after('sk_menkeu_no');
            // KEP Dewan Komisioner OJK sebagai Profesi Penunjang Sektor Jasa
            // Keuangan (No. :ojk_kep di Penjelasan Status Penilai).
            $table->string('ojk_kep_no')->nullable()->after('sttd_ojk_no');
            $table->string('klasifikasi')->nullable()->after('ojk_kep_no');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'jabatan',
                'partner_status',
                'mappi_no',
                'rmk_no',
                'izin_menkeu_no',
                'sk_menkeu_no',
                'sttd_ojk_no',
                'ojk_kep_no',
                'klasifikasi',
            ]);
        });
    }
};
