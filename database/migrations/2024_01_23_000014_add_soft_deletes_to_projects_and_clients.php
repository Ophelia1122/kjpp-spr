<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hapus sementara (soft delete) untuk proyek & klien (2026-09-20).
 *
 * Sebelumnya tombol hapus membuang baris secara permanen — satu salah klik
 * pada proyek Batal ikut membuang seluruh invoice-nya tanpa jalan kembali.
 * Dengan kolom deleted_at, data pindah ke halaman "Sampah" dulu dan bisa
 * dipulihkan; isinya dibersihkan otomatis setelah 30 hari
 * (lihat App\Console\Commands\PurgeTrash).
 *
 * Murni menambah kolom: data yang sudah ada tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['projects', 'clients'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['projects', 'clients'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropSoftDeletes();
                });
            }
        }
    }
};
