<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * proposal_purpose semula ENUM berisi empat tujuan penilaian saja. Sejak
 * kantor menerima Jasa Konsultasi (2026-09-25), kolom ini juga menampung
 * jenis konsultasinya — "Kajian Kewajaran RAB", "Studi Kelayakan",
 * "Pengawasan Proyek" — supaya filter List Project, export Excel, dan Teks
 * Baku Proposal per tujuan tetap bekerja tanpa perlakuan khusus.
 *
 * Nilai yang boleh masuk tetap dibatasi di ProposalController; pembatasan
 * pindah dari database ke validasi karena daftarnya kini bergantung pada
 * jenis layanan.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `projects` MODIFY `proposal_purpose` VARCHAR(60) NOT NULL DEFAULT 'Jual Beli'");
    }

    public function down(): void
    {
        // Proyek konsultasi tidak punya padanan di ENUM lama — kembalikan ke
        // nilai bawaan supaya perubahan tipe kolomnya tidak gagal.
        DB::table('projects')
            ->whereNotIn('proposal_purpose', ['Jual Beli', 'Penjaminan Utang', 'Lelang', 'Pelaporan Keuangan'])
            ->update(['proposal_purpose' => 'Jual Beli']);

        DB::statement("ALTER TABLE `projects` MODIFY `proposal_purpose` ENUM('Jual Beli','Penjaminan Utang','Lelang','Pelaporan Keuangan') NOT NULL DEFAULT 'Jual Beli'");
    }
};
