<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jenis pekerjaan "Kajian Kewajaran RAB" jadi "Kajian" saja (2026-09-26,
 * permintaan user): isi bab proposalnya sama untuk kajian proyek, kajian
 * harga sewa, maupun kajian RAB — pembedanya hanya objek yang dikaji.
 *
 * Nilainya tersimpan sebagai teks di beberapa kolom, jadi baris lama ikut
 * diganti supaya proyek yang sudah ada tetap terbaca sistem.
 */
return new class extends Migration
{
    private const LAMA = 'Kajian Kewajaran RAB';
    private const BARU = 'Kajian';

    public function up(): void
    {
        $this->ganti(self::LAMA, self::BARU);
    }

    public function down(): void
    {
        $this->ganti(self::BARU, self::LAMA);
    }

    private function ganti(string $dari, string $ke): void
    {
        DB::table('projects')->where('consulting_type', $dari)->update(['consulting_type' => $ke]);
        DB::table('projects')->where('proposal_purpose', $dari)->update(['proposal_purpose' => $ke]);

        if (Schema::hasTable('proposal_section_defaults')) {
            DB::table('proposal_section_defaults')->where('proposal_purpose', $dari)
                ->update(['proposal_purpose' => $ke]);
        }
    }
};
