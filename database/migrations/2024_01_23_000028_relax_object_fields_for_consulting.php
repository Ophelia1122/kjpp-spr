<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Objek pekerjaan pada proposal Jasa Konsultasi hanya berisi LOKASI
 * (2026-09-25, permintaan user). Kategori aset, bentuk kepemilikan, dan nama
 * pemilik adalah data khusus penilaian properti, jadi kolomnya dilonggarkan
 * agar boleh kosong. Lokasi tetap wajib.
 *
 * Proposal penilaian tidak berubah: validasi di ProposalController tetap
 * mewajibkan ketiga kolom itu untuk service_type = Penilaian.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['asset_category', 'ownership_form', 'owner_name'] as $kolom) {
            DB::statement("ALTER TABLE `project_valuation_objects` MODIFY `{$kolom}` VARCHAR(255) NULL");
        }
    }

    public function down(): void
    {
        foreach (['asset_category', 'ownership_form', 'owner_name'] as $kolom) {
            DB::statement("UPDATE `project_valuation_objects` SET `{$kolom}` = '' WHERE `{$kolom}` IS NULL");
            DB::statement("ALTER TABLE `project_valuation_objects` MODIFY `{$kolom}` VARCHAR(255) NOT NULL");
        }
    }
};
