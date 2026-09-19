<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Zona waktu aplikasi pindah dari UTC ke Asia/Jakarta (2026-09-14, feedback
 * user). Laravel menyimpan waktu sebagai "jam dinding" zona aplikasi, jadi
 * semua waktu lama (tersimpan dalam jam UTC) digeser +7 jam supaya tetap
 * menunjukkan saat yang sama setelah dibaca sebagai WIB.
 *
 * Mencakup SEMUA kolom datetime/timestamp di database ini (created_at,
 * updated_at, review_*_at, cancelled_at, dst). Kolom tanggal saja (date)
 * tidak berubah. Hanya MySQL/MariaDB.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->shift('DATE_ADD');
    }

    public function down(): void
    {
        $this->shift('DATE_SUB');
    }

    private function shift(string $fn): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $columns = DB::select(
            "SELECT TABLE_NAME AS t, COLUMN_NAME AS c FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND DATA_TYPE IN ('datetime', 'timestamp')",
            [DB::getDatabaseName()]
        );

        foreach ($columns as $col) {
            DB::statement("UPDATE `{$col->t}` SET `{$col->c}` = {$fn}(`{$col->c}`, INTERVAL 7 HOUR) WHERE `{$col->c}` IS NOT NULL");
        }
    }
};
