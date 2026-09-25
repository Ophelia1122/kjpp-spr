<?php

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
