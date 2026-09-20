<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Jadwal
|--------------------------------------------------------------------------
| Berjalan bila cron memanggil "php artisan schedule:run" tiap menit
| (di NAS: lihat docs/tutorial-deploy-nas-tailscale.md). Tanpa cron, perintah
| di bawah tetap bisa dijalankan manual.
*/

// Log Aktivitas dipangkas tiap awal bulan, simpan 24 bulan terakhir (2026-09-20).
Schedule::command('audit:prune --months=24')->monthlyOn(1, '02:30');
