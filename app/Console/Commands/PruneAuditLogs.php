<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

/**
 * Pembersih Log Aktivitas (2026-09-20). Tabel audit_logs bertambah pada setiap
 * aksi pengguna dan tidak pernah menyusut, sehingga lama-lama menjadi tabel
 * terbesar dan memperlambat halaman Log Aktivitas.
 *
 * Dijalankan terjadwal (lihat routes/console.php) atau manual:
 *   php artisan audit:prune            -> hapus log lebih tua dari 24 bulan
 *   php artisan audit:prune --months=12
 *   php artisan audit:prune --dry-run  -> hanya hitung, tidak menghapus
 */
class PruneAuditLogs extends Command
{
    protected $signature = 'audit:prune {--months=24 : Umur log yang dipertahankan} {--dry-run : Hitung saja, tanpa menghapus}';

    protected $description = 'Hapus Log Aktivitas yang lebih tua dari sekian bulan';

    public function handle(): int
    {
        $months = max(1, (int) $this->option('months'));
        $cutoff = now()->subMonths($months);

        $query = AuditLog::where('created_at', '<', $cutoff);
        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info("Tidak ada log lebih tua dari {$months} bulan.");

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("{$total} log lebih tua dari {$months} bulan (sebelum {$cutoff->toDateString()}) akan dihapus.");

            return self::SUCCESS;
        }

        // Dihapus bertahap supaya tabel tidak terkunci lama di NAS.
        $deleted = 0;
        do {
            $batch = AuditLog::where('created_at', '<', $cutoff)->limit(1000)->delete();
            $deleted += $batch;
        } while ($batch > 0);

        $this->info("{$deleted} log dihapus (lebih tua dari {$months} bulan).");

        return self::SUCCESS;
    }
}
