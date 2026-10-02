<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Editor Teks Baku per Tujuan (Pengaturan Sistem) dulu menampilkan bab
 * Laporan & Objek dengan nilai PROYEK CONTOH — SLA 12/5/7 hari, jenis
 * laporan "Terinci", dan nama "PT Contoh Pemberi Tugas" — lalu membeku ke
 * teks yang disimpan, sehingga semua proposal ikut mencetak angka itu
 * (2026-10-02, laporan user: SLA "5 (lima)" tidak ikut proyek).
 *
 * Hanya kalimat yang persis berasal dari proyek contoh yang dikembalikan jadi
 * variabel. Teks yang sudah diubah sendiri oleh admin (mis. angka lain)
 * tidak disentuh. Data yang diubah dicatat ke log supaya bisa diperiksa.
 */
return new class extends Migration
{
    private const GANTI = [
        'laporan' => [
            'jangka waktu **12 (dua belas)** hari kerja'       => 'jangka waktu **:sla_total** hari kerja',
            'jangka waktu 12 (dua belas) hari kerja'           => 'jangka waktu :sla_total hari kerja',
            'dalam waktu **5 (lima)** hari kerja setelah inspeksi' => 'dalam waktu **:sla_draft** hari kerja setelah inspeksi',
            'dalam waktu 5 (lima) hari kerja setelah inspeksi'     => 'dalam waktu :sla_draft hari kerja setelah inspeksi',
            'dalam waktu **7 (tujuh)** hari kerja setelah laporan' => 'dalam waktu **:sla_final** hari kerja setelah laporan',
            'dalam waktu 7 (tujuh) hari kerja setelah laporan'     => 'dalam waktu :sla_final hari kerja setelah laporan',
            'adalah **Laporan Penilaian Terinci (Comprehensive Style Report)**' => 'adalah **:jenis_laporan**',
            'adalah Laporan Penilaian Terinci (Comprehensive Style Report)'     => 'adalah :jenis_laporan',
        ],
        'objek' => [
            'objek penilaian adalah **PT Contoh Pemberi Tugas**' => 'objek penilaian adalah **:nama_sertifikat**',
            'objek penilaian adalah PT Contoh Pemberi Tugas'     => 'objek penilaian adalah :nama_sertifikat',
            'penugasan ini adalah **PT Contoh Pemberi Tugas**'   => 'penugasan ini adalah **:pemberi_tugas**',
            'penugasan ini adalah PT Contoh Pemberi Tugas'       => 'penugasan ini adalah :pemberi_tugas',
        ],
    ];

    public function up(): void
    {
        foreach (self::GANTI as $key => $pasangan) {
            $rows = DB::table('proposal_section_defaults')->where('section_key', $key)->get(['id', 'proposal_purpose', 'body']);

            foreach ($rows as $row) {
                $baru = strtr($row->body, $pasangan);
                if ($baru === $row->body) {
                    continue;
                }

                DB::table('proposal_section_defaults')->where('id', $row->id)->update(['body' => $baru]);
                logger()->info("Teks baku '{$key}' (tujuan: '{$row->proposal_purpose}') dikembalikan ke variabel.");
            }
        }
    }

    public function down(): void
    {
        // Sengaja kosong: mengembalikan angka contoh justru memunculkan lagi bug-nya.
    }
};
