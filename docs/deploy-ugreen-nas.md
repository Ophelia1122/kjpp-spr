# Catatan Deploy ke UGREEN NAS

Checklist untuk memindahkan aplikasi KJPP dari laptop (Windows + Laragon) ke UGREEN NAS.
Disusun 15 September 2026. **Cek ulang tiap poin saat benar-benar deploy** — konfigurasi bisa sudah berubah.

## 0. Perangkat: UGREEN NASync DXP2800

Spesifikasi umum model ini (**cocokkan dengan unit kantor** di Control Panel UGOS Pro, terutama RAM):

| Komponen | Spesifikasi umum | Dampak ke aplikasi |
|---|---|---|
| CPU | Intel N100, 4 core, **x86_64** | Image Docker PHP/MySQL/LibreOffice standar (amd64) langsung jalan — tidak ada masalah ARM |
| RAM | 8 GB DDR5 (bisa ditambah) | Dibagi dengan UGOS, MySQL, PHP-FPM, LibreOffice — beri batas memori per container |
| Penyimpanan | 2 bay HDD/SSD + 2 slot M.2 NVMe | Taruh container & data MySQL di **SSD NVMe** (jauh lebih cepat dari HDD); HDD untuk file & backup |
| Jaringan | 2,5 GbE, terhubung internet | Cukup untuk pemakaian kantor; Tailwind CDN tidak jadi masalah |
| OS | UGOS Pro, mendukung Docker (termasuk compose) | Deploy lewat Docker compose: container app (Nginx + PHP-FPM), MySQL, dan LibreOffice ada di image app |

Catatan beban untuk N100 + 8 GB:
- **Konversi proposal ke PDF (LibreOffice) adalah proses terberat** — beberapa detik dan ratusan MB RAM
  per konversi. Aman untuk pemakaian kantor biasa; kalau banyak user membuat PDF bersamaan, pertimbangkan
  antrean (queue) supaya konversi berjalan satu per satu.
- MySQL: batasi `innodb_buffer_pool_size` sekitar 512 MB–1 GB supaya tidak menghabiskan RAM NAS.
- Pakai **RAID 1** di 2 bay untuk data, tetap simpan **backup di luar NAS** (RAID bukan backup).

Asumsi: NAS menjalankan aplikasi lewat **Docker** (UGOS Pro mendukung Docker). Repo sudah punya `Dockerfile`.

---

## 1. Performa (hasil pengukuran di laptop, 15 Sep 2026)

Di laptop, tiap halaman butuh **1,9–3,4 detik** padahal kerja aplikasinya sendiri hanya **20–80 ms**
(maks. 25 query, ±0,9 ms per query). Hampir seluruh waktu habis untuk menyalakan Laravel dari nol
di setiap request. Penyebab & yang WAJIB di NAS:

| Hal | Laptop (dev) | Wajib di NAS (produksi) |
|---|---|---|
| OPcache | awalnya mati | **aktif**, `opcache.validate_timestamps=0` (restart container tiap deploy) |
| Web server | `php artisan serve` / `php -S` (1 request sekaligus) | **nginx/Apache + PHP-FPM**, jangan `php -S` |
| Cache config/route/event/view | tidak di-cache | jalankan `php artisan optimize` setiap deploy |
| `APP_ENV` / `APP_DEBUG` | `local` / `true` | `production` / **`false`** |
| `LOG_LEVEL` | `debug` | `warning` atau `error` |
| `SESSION_DRIVER` / `CACHE_STORE` | `database` | `file` (atau `redis` kalau ada container Redis) |

Setelan OPcache yang disarankan untuk produksi:

```ini
zend_extension=opcache
opcache.enable=1
opcache.memory_consumption=192
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

Semua setelan di atas **sudah dipasang** di file deploy (`Dockerfile`, `docker/php-production.ini`,
`docker/php-fpm-kjpp.conf`, `docker/env.nas.example`). Saat container start, `docker/entrypoint.sh`
otomatis menjalankan `storage:link`, `migrate --force`, dan `optimize`.

**Langkah pemasangan lengkap: [`tutorial-install-ugreen-nas.md`](tutorial-install-ugreen-nas.md).**

**Akses dari luar kantor lewat Tailscale: [`tutorial-deploy-nas-tailscale.md`](tutorial-deploy-nas-tailscale.md).**

## 2. Tailwind (browser build, disimpan lokal)

Tailwind browser v4.3.3 kini disimpan di `public/js/tailwindcss-browser.js` (14 Sep 2026) — tidak lagi
bergantung pada `cdn.jsdelivr.net`. CSS masih dikompilasi di browser tiap buka halaman; kalau suatu saat
ingin lebih cepat, Tailwind bisa di-build jadi file CSS statis (Vite) — bukan prioritas.

## 3. Dokumen proposal (.docx → PDF)

- **LibreOffice headless** wajib ada untuk konversi proposal ke PDF. `.env` laptop menunjuk path Windows
  (`C:\Program Files\LibreOffice\...`); di Docker cukup `LIBREOFFICE_BIN=soffice` (sudah ada di `Dockerfile`).
  LibreOffice cukup berat — pastikan CPU/RAM NAS memadai.
- **Font Arial Narrow** (`public/fonts/arialn*.ttf`) sengaja **tidak masuk git** (font berlisensi Microsoft).
  Harus disalin manual ke NAS, atau ganti font pengganti (Liberation Sans Narrow / Arimo), atau pakai
  Helvetica lewat `config('kjpp.pdf_font')`.

## 4. Data & penyimpanan

- `php artisan storage:link` di NAS; folder `storage/app/public` (barcode surat tugas, upload) harus di
  **volume persisten** supaya tidak hilang saat container dibuat ulang.
- Folder `storage/` & `bootstrap/cache/` harus bisa ditulis oleh user PHP-FPM.
- Database MySQL: pakai volume persisten + **jadwal backup rutin**.
- Migrasi data yang bergantung isi database (sudah jalan di laptop, ikut terbawa bila database diimpor):
  - `2024_01_21_000004_move_default_signatory_to_user` — mencari user bernama persis
    `config('kjpp.signatory.name')` berjabatan Penanggung Jawab; di database kosong otomatis dilewati.
  - `2024_01_22_000001_merge_ojk_kep_into_sttd_ojk` — menghapus kolom `ojk_kep_*` setelah digabung ke STTD OJK.

## 5. `.env` yang harus disesuaikan

`APP_URL` (alamat NAS / domain), `APP_KEY` (pakai yang sama bila database diimpor dari laptop),
`DB_*`, `LIBREOFFICE_BIN`, `APP_TIMEZONE`/locale (`Asia/Jakarta`, `id`), serta poin performa di bagian 1.

## 6. Lain-lain

- PHP laptop **8.3.33** — pakai image PHP 8.3 yang kompatibel.
- `QUEUE_CONNECTION=database`: kalau nanti ada job antrean, jalankan `php artisan queue:work` sebagai
  service terpisah; kalau ada jadwal, pasang cron `php artisan schedule:run` tiap menit.
- HTTPS lewat reverse proxy UGOS bila diakses dari luar kantor.
