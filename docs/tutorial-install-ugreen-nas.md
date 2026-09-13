# Tutorial Pemasangan Aplikasi KJPP ke UGREEN NASync DXP2800

Panduan langkah demi langkah dari laptop (Laragon) sampai aplikasi bisa dibuka rekan kantor lewat NAS.
Checklist latar belakang & alasan teknis ada di [`deploy-ugreen-nas.md`](deploy-ugreen-nas.md).

> **Perkiraan waktu:** 1–2 jam untuk pemasangan pertama (sebagian besar menunggu build image di NAS).
> **Istilah:** "folder proyek" = folder aplikasi di NAS, contoh `/volume1/docker/kjpp-app`.

---

## Bagian A — Persiapan di NAS

### A1. Cek perangkat
1. Login ke UGOS Pro (browser: `http://IP-NAS`) dengan akun **administrator**.
2. Buka **Control Panel → Info Perangkat / Hardware**, catat **RAM** (bawaan 8 GB).
3. Pastikan ada **storage pool**. Kalau terpasang SSD M.2 NVMe, sebaiknya folder proyek ditaruh di
   volume SSD (lebih cepat untuk database).

### A2. Beri NAS alamat IP tetap
Rekan kantor akan membuka aplikasi lewat IP NAS, jadi IP tidak boleh berubah-ubah.
1. **Control Panel → Jaringan** → pilih port LAN → atur **IP manual/statis** (atau reservasi DHCP di router).
2. Catat IP-nya, contoh `192.168.1.100`. Dipakai di seluruh tutorial ini.

### A3. Pasang aplikasi Docker
1. Buka **App Center**, cari **Docker**, klik **Install**.
2. Buka Docker sekali sampai tampil halaman utamanya.

### A4. Aktifkan SSH (sementara)
Diperlukan untuk membangun image & memindahkan database.
1. **Control Panel → Terminal** → aktifkan **SSH** (port bawaan 22).
2. Atur **waktu nonaktif otomatis** dan batasi akses hanya jaringan lokal.
3. Hanya akun admin yang bisa login SSH.

### A5. Buat folder proyek
1. Buka **File (Files)** → buat shared folder **`docker`** (kalau belum ada).
2. Di dalamnya buat folder **`kjpp-app`**.
3. Aktifkan akses **SMB** untuk shared folder `docker` supaya bisa diisi dari laptop Windows
   (Control Panel → File Services → SMB).

---

## Bagian B — Persiapan di Laptop

### B1. Pastikan kode terbaru sudah di GitHub
Semua perubahan sudah di-push ke branch `feat/proposal-word-pdf-and-rework`
(repo `Ophelia1122/kjpp-spr`). Folder `C:\laragon\www\kjpp-app` di laptop berisi versi yang sama.

### B2. Backup database laptop
Buka PowerShell di laptop, jalankan:

```powershell
& "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe" -u root --single-transaction --routines --default-character-set=utf8mb4 spr_db > "$env:USERPROFILE\Desktop\kjpp_db.sql"
```

Kalau MySQL Laragon memakai password, tambahkan `-p` (akan diminta mengetik password).
Hasilnya file **`kjpp_db.sql`** di Desktop.

### B3. Catat APP_KEY laptop
Buka `C:\laragon\www\kjpp-app\.env`, salin baris `APP_KEY=base64:....`. Nilai ini **harus sama** di NAS.

### B4. Salin folder aplikasi ke NAS
1. Di File Explorer buka `\\192.168.1.100\docker\kjpp-app` (login dengan akun NAS).
2. Salin **isi** `C:\laragon\www\kjpp-app` ke sana, **kecuali** folder berikut (tidak perlu, besar):
   `vendor`, `node_modules`, `storage\logs`, `.git`.
3. Pastikan folder **`public\fonts`** (font Arial Narrow) **ikut tersalin** — tidak ada di GitHub.
4. Salin juga `kjpp_db.sql` dari Desktop ke folder yang sama.

> Alternatif tanpa SMB: `git clone` repo di NAS lewat SSH, lalu salin `public/fonts` secara manual.

---

## Bagian C — Konfigurasi di NAS

### C1. Buat file `.env`
1. Di folder `\\192.168.1.100\docker\kjpp-app`, salin `docker\env.nas.example` → ubah namanya jadi **`.env`**
   (di folder utama `kjpp-app`, sejajar `docker-compose.yml`). Kalau sudah ada `.env` hasil salinan dari
   laptop, **timpa** dengan isi `env.nas.example`.
2. Buka `.env` dengan Notepad, isi:
   - `APP_KEY=` → tempel nilai dari langkah B3
   - `APP_URL=http://192.168.1.100:8080` → sesuaikan IP NAS
   - `DB_PASSWORD=` dan `DB_ROOT_PASSWORD=` → buat dua password kuat (catat di tempat aman)
3. Simpan.

### C2. Cek port
Aplikasi memakai port **8080**. Kalau sudah dipakai aplikasi lain di NAS, ganti `"8080:80"` di
`docker-compose.yml` (misal `"8090:80"`) **dan** sesuaikan `APP_URL`.

---

## Bagian D — Menjalankan Aplikasi

### D1. Masuk SSH
Dari PowerShell laptop:

```powershell
ssh namaadmin@192.168.1.100
```

Lalu masuk ke folder proyek (nama volume ditulis huruf kecil):

```bash
cd /volume1/docker/kjpp-app
```

Perintah `docker` di bawah mungkin perlu diawali `sudo`.

### D2. Nyalakan database dulu

```bash
sudo docker compose up -d db
sudo docker compose ps
```

Tunggu sampai kolom STATUS `kjpp-db` menunjukkan **healthy** (±30–60 detik; ulangi `ps`).

### D3. Pindahkan data dari laptop

```bash
sudo docker exec -i kjpp-db sh -c 'exec mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < kjpp_db.sql
```

Tidak ada pesan = berhasil. Cek jumlah user:

```bash
sudo docker exec -it kjpp-db sh -c 'exec mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "select count(*) from users;"'
```

### D4. Build & nyalakan aplikasi

```bash
sudo docker compose up -d --build app
```

Build pertama **10–30 menit** (mengunduh PHP, LibreOffice, dsb.). Setelah selesai, pantau log:

```bash
sudo docker compose logs -f app
```

Tunggu sampai tampil baris **`INFO success: nginx entered RUNNING state`**, lalu tekan `Ctrl + C`.
Saat start, container otomatis: membuat struktur folder storage, `storage:link`, menjalankan migrasi
yang belum ada, dan membuat cache (`optimize`).

### D5. Buka aplikasi
Dari komputer mana pun di jaringan kantor: **`http://192.168.1.100:8080`**

---

## Bagian E — Uji Setelah Pemasangan

Centang satu per satu:

- [ ] Login dengan akun yang ada di laptop → masuk ke **Beranda**
- [ ] Buka **List Project** → data proyek dari laptop muncul
- [ ] Buka satu proyek → **Unduh Word** proposal berhasil
- [ ] **Cetak PDF** proposal berhasil (uji LibreOffice; pertama kali bisa 10–20 detik)
- [ ] Cetak **Invoice** dan **Kwitansi** (2 lembar per halaman)
- [ ] Cetak **Surat Tugas** → font Arial Narrow tampil (bila `public/fonts` tersalin)
- [ ] **Export Excel** di List Project
- [ ] Buat klien baru lewat modal di form proposal
- [ ] Upload barcode Surat Tugas → gambarnya tampil

---

## Bagian F — Pemakaian Rutin

### Update aplikasi (setelah ada perubahan kode)
1. Salin ulang file yang berubah ke `\\192.168.1.100\docker\kjpp-app` (jangan timpa `.env`,
   `storage-data`, `mysql-data`).
2. SSH, lalu:

```bash
cd /volume1/docker/kjpp-app
sudo docker compose up -d --build app
```

Migrasi database baru dijalankan otomatis saat container start.

### Backup database (lakukan rutin, misal tiap hari)

```bash
cd /volume1/docker/kjpp-app
sudo docker exec kjpp-db sh -c 'exec mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" --single-transaction "$MYSQL_DATABASE"' > backup-$(date +%F).sql
```

Simpan salinan backup **di luar NAS** (laptop / cloud). Folder penting untuk dicadangkan:
`mysql-data`, `storage-data`, dan file `.env`.

### Perintah berguna

| Tujuan | Perintah |
|---|---|
| Status container | `sudo docker compose ps` |
| Lihat log aplikasi | `sudo docker compose logs --tail=100 app` |
| Restart aplikasi | `sudo docker compose restart app` |
| Matikan semua | `sudo docker compose down` (data tetap aman) |
| Masuk ke container | `sudo docker exec -it kjpp-app bash` |
| Log Laravel | `tail -n 50 storage-data/logs/laravel.log` |

---

## Bagian G — Masalah Umum

| Gejala | Penyebab & solusi |
|---|---|
| Build gagal saat `apt-get` / `composer` | Koneksi internet NAS terputus → ulangi `docker compose up -d --build app` |
| Container `app` langsung berhenti, log: `.env tidak ditemukan` | File `.env` belum dibuat di folder proyek (Bagian C1) |
| `SQLSTATE[HY000] [2002]` / tidak bisa konek DB | `kjpp-db` belum healthy, atau `DB_PASSWORD` di `.env` berubah setelah database dibuat. Password MySQL hanya diset saat `mysql-data` pertama kali dibuat |
| Halaman error 500 | `sudo docker compose logs --tail=100 app` dan `storage-data/logs/laravel.log` |
| Semua user ter-logout / "419 Page Expired" | `APP_KEY` di NAS beda dengan laptop → samakan (Bagian B3), lalu `sudo docker compose restart app` |
| PDF proposal gagal | Lihat log; biasanya RAM kurang saat banyak konversi bersamaan. Coba lagi, atau tambah RAM NAS |
| Font Surat Tugas bukan Arial Narrow | Folder `public/fonts` belum tersalin → salin lalu `sudo docker compose up -d --build app` |
| Port 8080 bentrok | Ganti port di `docker-compose.yml` & `APP_URL` (Bagian C2) |
| Perubahan kode tidak muncul | OPcache produksi tidak membaca ulang file → wajib `sudo docker compose up -d --build app` |

---

## Bagian H — Keamanan

- Matikan **SSH** setelah selesai (Control Panel → Terminal) — nyalakan hanya saat perlu.
- Jangan membuka port 8080 ke internet dari router. Kalau perlu diakses dari luar kantor, pakai
  **reverse proxy + HTTPS** atau VPN.
- `.env` berisi password — jangan dibagikan; shared folder `docker` cukup diakses admin.
- Ganti password akun admin aplikasi yang masih bawaan.

---

## Catatan

- File deploy (`Dockerfile`, `docker-compose.yml`, `docker/*`) disiapkan 15 September 2026, **belum pernah
  diuji build** karena laptop tidak memiliki Docker. Uji pertama kali akan terjadi di NAS — kalau ada error
  saat build/start, catat pesan lognya.
- Menu UGOS Pro (nama menu Control Panel/App Center) bisa sedikit berbeda antar versi firmware.
- Alternatif tanpa SSH: Docker → **Project → Create**, arahkan ke folder `/volume1/docker/kjpp-app`
  berisi `docker-compose.yml`. Namun langkah impor database (D3) tetap paling mudah lewat SSH.
