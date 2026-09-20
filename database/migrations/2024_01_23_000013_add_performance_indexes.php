<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks untuk kolom yang dipakai filter & urutan (2026-09-20).
 *
 * Sebelumnya hanya nomor proposal/invoice, nama klien, dan kunci asing yang
 * terindeks. Halaman List Project, Dashboard Pembayaran, SPJ Surveyor, dan Log
 * Aktivitas menyaring lewat kolom di bawah ini; tanpa indeks setiap halaman
 * memindai seluruh tabel begitu datanya membesar.
 *
 * Murni menambah indeks: tidak ada kolom/baris yang berubah, aman dijalankan
 * pada database yang sudah berisi data.
 */
return new class extends Migration
{
    /** Indeks komposit dipilih mengikuti pasangan filter yang benar-benar dipakai. */
    private const INDEXES = [
        'projects' => [
            ['status', 'created_at'],   // List Project: filter status + urutan terbaru
            ['survey_date'],            // SPJ Surveyor & kartu "survei bulan ini"
            ['review_status'],          // antrean review & tahap produksi
            ['printed_at'],             // statistik "selesai bulan ini"
            ['payment_scheme'],         // Dashboard Pembayaran (sisa tagihan)
        ],
        'invoices' => [
            ['status', 'invoice_date'], // daftar invoice & hitung tertunggak
            ['payment_date'],           // rekap "sudah diterima" per periode
        ],
        'audit_logs' => [
            ['created_at'],             // urutan log terbaru
            ['action'],                 // filter jenis aksi
            ['user_id', 'created_at'],  // riwayat per pengguna & "login terakhir"
        ],
        'project_valuation_objects' => [
            ['project_id'],             // rincian objek per proyek
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $indexes) {
                foreach ($indexes as $columns) {
                    if ($this->missingColumn($table, $columns) || $this->indexExists($table, $this->indexName($table, $columns))) {
                        continue;
                    }
                    $blueprint->index($columns);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $indexes) {
                foreach ($indexes as $columns) {
                    $name = $this->indexName($table, $columns);
                    if ($this->indexExists($table, $name)) {
                        $blueprint->dropIndex($name);
                    }
                }
            });
        }
    }

    private function indexName(string $table, array $columns): string
    {
        return $table . '_' . implode('_', $columns) . '_index';
    }

    private function missingColumn(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return true;
            }
        }

        return false;
    }

    private function indexExists(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn ($index) => $index['name'] === $name);
    }
};
