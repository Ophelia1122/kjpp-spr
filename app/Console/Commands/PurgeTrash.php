<?php

namespace App\Console\Commands;

use App\Http\Controllers\TrashController;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Console\Command;

/**
 * Pembersih Sampah (2026-09-20): proyek & klien yang sudah lebih dari
 * TrashController::RETENTION_DAYS hari berada di Sampah dibuang permanen.
 *
 *   php artisan trash:purge --dry-run
 *   php artisan trash:purge
 */
class PurgeTrash extends Command
{
    protected $signature = 'trash:purge {--days= : Umur isi Sampah yang dipertahankan} {--dry-run : Hitung saja, tanpa menghapus}';

    protected $description = 'Hapus permanen isi Sampah yang sudah lewat masa simpan';

    public function handle(): int
    {
        $days   = (int) ($this->option('days') ?: TrashController::RETENTION_DAYS);
        $cutoff = now()->subDays($days);

        $projects = Project::onlyTrashed()->where('deleted_at', '<', $cutoff)->get();
        $clients  = Client::onlyTrashed()->where('deleted_at', '<', $cutoff)->get();

        if ($projects->isEmpty() && $clients->isEmpty()) {
            $this->info("Tidak ada isi Sampah yang lebih tua dari {$days} hari.");

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("{$projects->count()} proyek & {$clients->count()} klien akan dihapus permanen (dibuang sebelum {$cutoff->toDateString()}).");

            return self::SUCCESS;
        }

        foreach ($projects as $project) {
            $project->forceDelete();
        }
        foreach ($clients as $client) {
            $client->forceDelete();
        }

        $this->info("{$projects->count()} proyek & {$clients->count()} klien dihapus permanen.");

        return self::SUCCESS;
    }
}
