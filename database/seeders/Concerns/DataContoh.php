<?php

namespace Database\Seeders\Concerns;

/**
 * Pengaman seeder berisi DATA CONTOH.
 *
 * Seeder yang memakai trait ini menolak berjalan di lingkungan production,
 * supaya `php artisan db:seed` tidak pernah menimpa data kantor yang asli —
 * termasuk mengembalikan kata sandi akun ke nilai contoh.
 *
 * Untuk sengaja mengisi data contoh di production (mis. saat menyiapkan
 * lingkungan uji coba), set SEED_DEMO=true di berkas .env.
 */
trait DataContoh
{
    protected function bolehIsiDataContoh(): bool
    {
        if (! app()->environment('production')) {
            return true;
        }

        if (filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        $this->command?->warn(
            class_basename(static::class) . ' dilewati: data contoh tidak dijalankan di production. '
            . 'Set SEED_DEMO=true bila memang disengaja.'
        );

        return false;
    }
}
