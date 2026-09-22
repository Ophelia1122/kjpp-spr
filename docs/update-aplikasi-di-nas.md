# Memperbarui Aplikasi SPR di NAS (tanpa mengulang setup)

Panduan menarik perubahan terbaru dari GitHub ke NAS yang **sudah berjalan**.
Database, file upload, dan pengaturan yang sudah ada di NAS **tidak diubah**.

> Disusun 20 September 2026. Pemasangan pertama kali ada di
> [`tutorial-deploy-nas-tailscale.md`](tutorial-deploy-nas-tailscale.md).

---

## Yang aman dan yang berbahaya

| Aman dilakukan | Jangan pernah dijalankan di NAS |
|---|---|
| `git fetch` / `git checkout` | `php artisan migrate:fresh` — menghapus semua tabel |
| `docker compose up -d --build app` | `php artisan db:seed` — menimpa data dengan contoh |
| `php artisan migrate --force` (otomatis saat container start) | `php artisan migrate:rollback` — membatalkan migrasi |
| Backup database | `docker compose down -v` — menghapus volume data |

File berikut **tidak** ikut repo, jadi tidak akan tertimpa saat menarik perubahan:
`.env`, `storage-data/`, `mysql-data/`, `tailscale-state/`, `public/fonts/`, `backups/`.

---

## Langkah update

### 1. Backup dulu (wajib, 1 menit)

```bash
cd /volume2/docker/kjpp-app && mkdir -p backups && sudo docker exec kjpp-db sh -c 'exec mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers "$MYSQL_DATABASE"' | gzip > backups/sebelum-update-$(date +%F_%H%M).sql.gz && ls -lh backups | tail -2
```

### 2. Cek apakah folder proyek sudah berupa repo git

```bash
cd /volume2/docker/kjpp-app && git rev-parse --is-inside-work-tree 2>/dev/null || echo "BUKAN REPO"
```

- Hasil `true` → lanjut ke **3A**.
- Hasil `BUKAN REPO` (folder hasil salin lewat SMB) → lanjut ke **3B** sekali saja, seterusnya cukup 3A.

### 3A. Tarik perubahan

Git dijalankan lewat container (`alpine/git`). `-c safe.directory=/repo` wajib,
karena pemilik folder berbeda dengan user di dalam container.

```bash
cd /volume2/docker/kjpp-app && sudo docker run --rm -v "$PWD":/repo -w /repo alpine/git -c safe.directory=/repo fetch origin 19092026-update && sudo docker run --rm -v "$PWD":/repo -w /repo alpine/git -c safe.directory=/repo checkout -f -B 19092026-update origin/19092026-update && sudo docker run --rm -v "$PWD":/repo -w /repo alpine/git -c safe.directory=/repo log --oneline -1
```

`checkout -f` hanya menimpa file yang dilacak git. File data dan `.env` tidak tersentuh.

### 3B. Jadikan folder sebagai repo (sekali saja)

Kalau `git` belum ada di NAS, pakai git lewat Docker:

```bash
cd /volume2/docker/kjpp-app && sudo docker run --rm -v "$PWD":/repo -w /repo alpine/git init -b 19092026-update
```

```bash
cd /volume2/docker/kjpp-app && sudo docker run --rm -v "$PWD":/repo -w /repo alpine/git remote add origin https://github.com/Ophelia1122/kjpp-spr.git
```

```bash
cd /volume2/docker/kjpp-app && sudo docker run --rm -v "$PWD":/repo -w /repo alpine/git fetch origin 19092026-update
```

```bash
cd /volume2/docker/kjpp-app && sudo docker run --rm -v "$PWD":/repo -w /repo alpine/git checkout -f -B 19092026-update origin/19092026-update
```

Untuk update berikutnya cukup dua perintah terakhir (`fetch` lalu `checkout -f`).

> Repo ini bersifat privat. Bila diminta login, buat Personal Access Token di
> GitHub (Settings → Developer settings → Tokens), lalu pakai sebagai password.
> Jangan menyimpan token di file yang ikut repo.

### 3C. Pastikan font Arial Narrow ada (wajib sebelum build)

File font berlisensi sehingga tidak ikut git. Tanpa file ini LibreOffice diam-diam
memakai **Liberation Sans Narrow** dan huruf proposal terlihat berbeda/kaku.

```bash
ls /volume2/docker/kjpp-app/public/fonts/
```

Harus ada `arialn.ttf`, `arialnb.ttf`, `arialni.ttf`, `arialnbi.ttf`. Kalau kosong,
salin dari laptop (`C:\laragon\www\kjpp-app\public\fonts\`) lewat SMB dulu.

### 4. Bangun ulang container aplikasi

```bash
cd /volume2/docker/kjpp-app && sudo docker compose up -d --build app queue scheduler && sudo docker compose logs -f app
```

Tunggu sampai muncul `nginx entered RUNNING state`, lalu tekan `Ctrl + C`.

Sejak 22 September 2026 image memasang **LibreOffice 25.2.7** resmi (versi terkunci,
sama dengan laptop). Build pertama sesudahnya mengunduh ±200 MB, jadi lebih lama.
Cek versinya:

```bash
sudo docker exec kjpp-app soffice --version
sudo docker exec kjpp-app fc-list | grep -ci "arial narrow"
```

Hasil harus `LibreOffice 25.2.7.2 ...` dan angka `4` (empat file font terpasang).

Saat start, container otomatis menjalankan `storage:link`, `migrate --force`, dan `optimize`.

### 4b. Nyalakan pekerja antrean & penjadwal (sekali saja)

Sejak 21 September 2026 ada dua container tambahan: `queue` (job latar) dan
`scheduler` (pembersih Sampah harian & pemangkas Log Aktivitas bulanan).

```bash
cd /volume2/docker/kjpp-app && sudo docker compose up -d queue scheduler && sudo docker compose ps
```

Keduanya memakai image yang sama dengan `app`, jadi tidak menambah waktu build.
Pada update berikutnya keduanya ikut terbarui lewat `docker compose up -d --build`.

### 5. Pastikan migrasi berhasil

```bash
sudo docker exec kjpp-app php artisan migrate:status | tail -6
```

Semua baris harus berstatus `Ran`. Kalau ada yang `Pending`, jalankan:

```bash
sudo docker exec kjpp-app php artisan migrate --force
```

### 6. Uji cepat

Buka aplikasi, lalu periksa: login, List Project berisi data, detail satu proyek,
Dashboard Pembayaran, dan cetak PDF satu proposal.

---

## Perubahan database pada update ini

Semua bersifat **menambah**. Tidak ada kolom yang dihapus, tidak ada data yang diubah.

| Migrasi | Isi | Efek ke data lama |
|---|---|---|
| `000010_create_project_appraisers_table` | Tabel baru penilai lapangan (1–3 orang per proyek) | Penilai yang sudah ada disalin ke tabel baru; kolom lama tetap dipakai |
| `000011_add_avatar_path_to_users` | Kolom foto profil | Kosong untuk user lama |
| `000012_add_payment_terms_to_projects` | Kolom persentase termin | Kosong = ikut bawaan skema (50/50 atau 100%) |
| `000013_add_performance_indexes` | Indeks pada kolom filter & urutan | Hanya mempercepat; isi tabel tidak berubah |
| `000014_add_soft_deletes_to_projects_and_clients` | Kolom `deleted_at` (Sampah) | Kosong untuk data lama; tidak ada yang terhapus |
| `000015_add_signature_options_to_projects` | Barcode tanda tangan, stempel, opsi Representatif terbatas | Proyek lama: tanpa barcode/stempel |
| `000016_create_whatsapp_notifications_table` | Tabel baru pengaturan notifikasi WhatsApp per tombol | Tidak ada data lama yang berubah |
| `000017_add_status_penilai_fields_to_users` | Izin Penilai Pertanahan & sektor OJK di biodata user | Kosong untuk user lama |

Kalau NAS sudah pernah menjalankan 000010–000012 (terlihat `Ran` di langkah 5),
update ini hanya menambahkan 000013 dan 000014.

---

## Setelah update: dua hal opsional

**Jadwal pembersih Log Aktivitas.** Log disimpan 24 bulan. Jalankan manual sesekali:

```bash
sudo docker exec kjpp-app php artisan audit:prune --dry-run
```

Hapus `--dry-run` untuk benar-benar menghapus. Untuk otomatis tiap bulan, pasang cron:

```bash
(sudo crontab -l 2>/dev/null; echo '* * * * * docker exec kjpp-app php artisan schedule:run >/dev/null 2>&1') | sudo crontab -
```

**Sampah.** Proyek & klien yang dihapus masuk `/sampah` dan bisa dipulihkan 30 hari.
Isinya dibersihkan otomatis oleh container `scheduler`. Untuk memeriksa manual:

```bash
sudo docker exec kjpp-app php artisan trash:purge --dry-run
```

---

## Sekali saja setelah update 22 September 2026

**Akun Penilai Publik.** Membuat akun Budi, Heru, Pipik, Hery (jabatan Penanggung
Jawab) dan melengkapi sektor OJK Arief. Aman diulang; data yang sudah diisi tidak
ditimpa. Cek dulu dengan `--dry-run`:

```bash
sudo docker exec kjpp-app php artisan kjpp:penilai-publik --dry-run
sudo docker exec kjpp-app php artisan kjpp:penilai-publik
```

Lalu di aplikasi: atur password keempat akun (Kelola Pengguna), dan pastikan nama
Penanggung Jawab memuat gelar lengkap, mis. `..., MAPPI (Cert.)` — gelar tidak lagi
ditambahkan otomatis.

**Bot WhatsApp.** Ikuti [bot-whatsapp.md](bot-whatsapp.md). Ringkasnya: isi
`WA_BOT_API_KEY` & `WA_DB_PASSWORD` di `.env`, `sudo docker compose up -d wa wa-db`,
buat instance `kjpp` & scan QR di `http://IP-NAS:8081/manager`, lalu di aplikasi isi
URL **`http://kjpp-wa:8080`**. Logout dulu sesi bot di laptop supaya nomor tidak
tersambung di dua tempat.

## Kalau update bermasalah

Kembali ke versi sebelumnya:

```bash
cd /volume2/docker/kjpp-app && sudo docker run --rm -v "$PWD":/repo -w /repo alpine/git checkout -f <commit-lama> && sudo docker compose up -d --build app
```

Memulihkan database dari backup langkah 1:

```bash
cd /volume2/docker/kjpp-app && gunzip -c backups/NAMA-FILE.sql.gz | sudo docker exec -i kjpp-db sh -c 'exec mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
```

Migrasi tidak perlu dibatalkan: kolom dan tabel tambahan tidak mengganggu versi lama.
