# Deploy Aplikasi SPR ke UGREEN NAS lewat Tailscale

Tutorial lengkap dari laptop (Laragon) sampai aplikasi bisa dibuka rekan kantor **dari mana saja**
di alamat HTTPS seperti `https://kjpp-nas.nama-tailnet.ts.net` — tanpa port forwarding di router,
tanpa membuka NAS ke internet.

> Disusun 15 September 2026. Latar belakang teknis: [`deploy-ugreen-nas.md`](deploy-ugreen-nas.md).
> Versi instalasi khusus jaringan lokal (tanpa Tailscale): [`tutorial-install-ugreen-nas.md`](tutorial-install-ugreen-nas.md).
>
> **Perkiraan waktu:** 1,5–2,5 jam (sebagian besar menunggu build image di NAS).
> **Contoh nilai di tutorial ini** — ganti dengan milik Anda:
> IP LAN NAS `192.168.1.100` · nama perangkat Tailscale `kjpp-nas` · nama tailnet `nama-tailnet.ts.net`
> · IP Tailscale NAS `100.x.y.z` · folder proyek `/volume1/docker/kjpp-app`.

---

## Gambaran jalur akses

```
Laptop / HP kantor ──(Tailscale, terenkripsi)──► NAS: tailscaled
                                                   │  https :443  (Tailscale Serve, sertifikat otomatis)
                                                   ▼
                                           http://127.0.0.1:8080 ──► container kjpp-app (nginx + PHP)
                                                                         ├─► kjpp-db (MySQL)
                                                                         └─► kjpp-wa (bot WhatsApp)
```

- Setiap perangkat yang mau membuka aplikasi **wajib terpasang Tailscale** dan masuk ke tailnet yang sama.
- HTTPS diurus Tailscale Serve; aplikasi di dalam container tetap HTTP biasa.
- Aplikasi sudah disiapkan untuk ini: `bootstrap/app.php` memakai `trustProxies`, jadi link CSS/JS/foto
  otomatis ikut `https://` saat dibuka lewat alamat `*.ts.net`.

---

## Bagian 0 — Yang perlu disiapkan

- [ ] Akun Tailscale yang akan menjadi **pemilik tailnet kantor** (sebaiknya akun email kantor, bukan pribadi).
- [ ] UGREEN NASync DXP2800 dengan UGOS Pro, terhubung internet.
- [ ] Laptop dengan Tailscale terpasang & login ke tailnet tersebut.
- [ ] Kode terbaru di laptop `C:\laragon\www\kjpp-app` (repo `Ophelia1122/kjpp-spr`).
- [ ] Folder `public\fonts` (Arial Narrow — tidak ada di GitHub).
- [ ] Waktu tanpa gangguan ± 2 jam; hindari jam kerja karena data laptop dibekukan saat dipindah.

---

## Bagian A — Persiapan NAS (di jaringan kantor)

Langkah ini dilakukan sekali, saat laptop masih satu jaringan dengan NAS.

1. **Login UGOS Pro** di browser: `http://192.168.1.100`, pakai akun administrator.
2. **IP tetap:** Control Panel → Jaringan → port LAN → IP manual/statis (atau reservasi DHCP di router).
3. **Docker:** App Center → cari **Docker** → Install → buka sekali.
4. **SSH:** Control Panel → Terminal → aktifkan SSH (port 22). Nanti bisa dimatikan lagi.
5. **Folder proyek:** Files → shared folder **`docker`** → buat folder **`kjpp-app`**.
   Aktifkan **SMB** untuk shared folder `docker` (Control Panel → File Services → SMB).
6. **Disk:** kalau ada SSD M.2 NVMe, taruh shared folder `docker` di volume SSD (database jauh lebih cepat).

---

## Bagian B — Siapkan tailnet di Tailscale Admin Console

Buka **https://login.tailscale.com/admin** dengan akun pemilik tailnet.

1. **MagicDNS:** menu **DNS** → pastikan **MagicDNS** aktif. Perangkat bisa dipanggil dengan nama
   (`kjpp-nas`), bukan hanya IP `100.x.y.z`.
2. **Sertifikat HTTPS:** masih di **DNS** → bagian **HTTPS Certificates** → **Enable HTTPS**.
   Wajib untuk alamat `https://kjpp-nas.nama-tailnet.ts.net`.
3. Catat **nama tailnet** yang tertera di halaman DNS (contoh `nama-tailnet.ts.net`).
4. **Auth key (hanya untuk Opsi 2 di Bagian C):** menu **Settings → Keys → Generate auth key**.
   - *Reusable*: **mati** · *Ephemeral*: **mati** · *Pre-approved*: nyala (bila device approval aktif).
   - Salin kuncinya (`tskey-auth-...`). Kunci hanya tampil sekali; perlakukan seperti password.

---

## Bagian C — Pasang Tailscale di NAS

Pilih **salah satu** opsi.

### Opsi 1 — Aplikasi Tailscale dari App Center (paling mudah, bila tersedia)

1. App Center → cari **Tailscale** → Install → buka.
2. Klik **Log in** → browser membuka halaman Tailscale → login dengan akun pemilik tailnet → **Connect**.
3. Lanjut ke langkah **C-akhir**.

Perintah `tailscale` di tutorial ini dijalankan lewat SSH NAS. Kalau perintah `tailscale` tidak ditemukan
di SSH, pakai Opsi 2.

### Opsi 2 — Tailscale sebagai container Docker (sudah disiapkan di `docker-compose.yml`)

Service `tailscale` memakai `network_mode: host`, sehingga NAS tampil sebagai satu perangkat di tailnet.
Service ini **tidak** ikut `docker compose up -d` biasa (memakai *profile*).

1. Pastikan file proyek sudah ada di NAS (Bagian D) dan `.env` sudah dibuat (Bagian E).
2. Isi di `.env`:
   ```ini
   TS_HOSTNAME=kjpp-nas
   TS_AUTHKEY=tskey-auth-xxxxxxxxxxxx
   ```
3. SSH ke NAS, lalu:
   ```bash
   cd /volume1/docker/kjpp-app
   sudo docker compose --profile tailscale up -d tailscale
   sudo docker logs --tail=30 kjpp-tailscale
   ```
   Berhasil bila perangkat `kjpp-nas` muncul di Admin Console → **Machines**.
4. **Hapus** nilai `TS_AUTHKEY` dari `.env` setelah berhasil (identitas sudah tersimpan di folder
   `tailscale-state`, kunci tidak dibutuhkan lagi).

Di Opsi 2, setiap perintah `tailscale ...` di tutorial ini ditulis sebagai
`sudo docker exec kjpp-tailscale tailscale ...`.

### C-akhir — Rapikan perangkat NAS di Admin Console

Menu **Machines** → baris NAS → tombol **⋯**:

1. **Edit machine name** → `kjpp-nas` (bila belum).
2. **Disable key expiry** — wajib. Tanpa ini NAS terputus sendiri tiap ±180 hari dan aplikasi tidak
   bisa diakses sampai ada yang login ulang.
3. Catat **IP Tailscale NAS** (`100.x.y.z`).

Cek dari laptop (PowerShell):

```powershell
tailscale ping kjpp-nas
```

---

## Bagian D — Pindahkan kode & data dari laptop

Bisa dari jaringan kantor maupun dari luar — lewat Tailscale alamat NAS cukup `kjpp-nas`.

### D1. Backup database laptop

PowerShell di laptop:

```powershell
& "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe" -u root --single-transaction --routines --triggers --no-tablespaces --default-character-set=utf8mb4 "--result-file=$env:USERPROFILE\Desktop\kjpp_db.sql" spr_db
```

Tambahkan `-p` bila MySQL Laragon memakai password.

> **Jangan** pakai `> kjpp_db.sql` di PowerShell — hasilnya UTF-16 dan gagal diimpor. Selalu `--result-file`.

### D2. Catat APP_KEY laptop

Buka `C:\laragon\www\kjpp-app\.env`, salin baris `APP_KEY=base64:...`. **Harus sama** di NAS
(kalau beda, data terenkripsi & sesi lama tidak terbaca).

### D3. Salin folder aplikasi

1. File Explorer → `\\kjpp-nas\docker\kjpp-app` (atau `\\192.168.1.100\docker\kjpp-app` di kantor)
   → login akun NAS.
2. Salin **isi** `C:\laragon\www\kjpp-app`, **kecuali**: `vendor`, `node_modules`, `.git`, `storage\logs`.
3. Pastikan **`public\fonts`** ikut tersalin.
4. Salin `kjpp_db.sql` dari Desktop ke folder yang sama.
5. **File upload** (barcode Surat Tugas, foto profil) ada di `storage\app\public` laptop →
   salin ke **`storage-data\app\public`** di NAS (buat foldernya bila belum ada).

> Alternatif tanpa SMB: SSH ke NAS → `git clone https://github.com/Ophelia1122/kjpp-spr.git kjpp-app`
> → checkout branch yang dipakai → salin `public/fonts` & upload secara manual.

---

## Bagian E — File `.env` di NAS

1. Di `\\kjpp-nas\docker\kjpp-app`, salin `docker\env.nas.example` menjadi **`.env`**
   (sejajar `docker-compose.yml`; timpa `.env` hasil salinan laptop).
2. Isi:

```ini
APP_KEY=base64:...                       # dari langkah D2
APP_URL=https://kjpp-nas.nama-tailnet.ts.net
DB_PASSWORD=...                          # password kuat baru
DB_ROOT_PASSWORD=...                     # password kuat baru
WA_BOT_API_KEY=...                       # kunci acak panjang (bot WhatsApp)
WA_DB_PASSWORD=...
TS_HOSTNAME=kjpp-nas                     # Opsi 2 saja
```

Catatan:
- `APP_URL` dipakai untuk link di pesan WhatsApp & dokumen — isi dengan alamat Tailscale.
- **Jangan** isi `SESSION_SECURE_COOKIE=true` selama masih ingin login lewat `http://192.168.1.100:8080`
  di kantor.
- Password MySQL hanya diset saat folder `mysql-data` pertama kali dibuat. Mengubahnya belakangan di
  `.env` tidak mengubah password database.

---

## Bagian F — Jalankan aplikasi

### F1. SSH ke NAS (lewat Tailscale)

```powershell
ssh namaadmin@kjpp-nas
```

```bash
cd /volume1/docker/kjpp-app
```

### F2. Database

```bash
sudo docker compose up -d db
sudo docker compose ps
```

Tunggu `kjpp-db` berstatus **healthy** (±30–60 detik), lalu impor data laptop:

```bash
sudo docker exec -i kjpp-db sh -c 'exec mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < kjpp_db.sql
sudo docker exec -it kjpp-db sh -c 'exec mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "select count(*) from users;"'
```

### F3. Build & nyalakan aplikasi + bot

> **Jangan deploy lewat menu Docker → Project di UGOS untuk pertama kali.** Menu itu hanya *mengunduh*
> image dari internet, padahal image `kjpp-app` harus **dibangun** dari `Dockerfile` di folder proyek.
> Gejalanya di Deployment Log: `pull access denied for kjpp-app, repository does not exist` dan
> `require 'docker login'`. Bangun lewat SSH dengan perintah di bawah (ada `--build`). Setelah image
> `kjpp-app:latest` ada di NAS, menu Project boleh dipakai untuk start/stop.

```bash
sudo docker compose up -d --build app
sudo docker compose up -d wa
sudo docker compose logs -f app
```

Build pertama 10–30 menit. Tunggu baris **`nginx entered RUNNING state`**, lalu `Ctrl + C`.
Saat start, container otomatis menjalankan `storage:link`, migrasi yang belum ada, dan `optimize`.

Uji lokal di NAS:

```bash
curl -I http://127.0.0.1:8080/login
```

Hasil `HTTP/1.1 200 OK` = aplikasi hidup.

---

## Bagian G — Nyalakan HTTPS dengan Tailscale Serve

Tailscale Serve menerima koneksi HTTPS dari tailnet dan meneruskannya ke aplikasi di port 8080.

```bash
# Opsi 1 (App Center)
sudo tailscale serve --bg 8080

# Opsi 2 (container)
sudo docker exec kjpp-tailscale tailscale serve --bg 8080
```

Cek:

```bash
sudo tailscale serve status          # Opsi 2: sudo docker exec kjpp-tailscale tailscale serve status
```

Keluaran yang benar kira-kira:

```
https://kjpp-nas.nama-tailnet.ts.net (tailnet only)
|-- / proxy http://127.0.0.1:8080
```

Buka dari laptop: **`https://kjpp-nas.nama-tailnet.ts.net`**. Kunjungan pertama bisa 10–60 detik
karena sertifikat sedang dibuat; setelah itu normal. Pengaturan `--bg` tersimpan dan tetap aktif
setelah NAS restart.

### Dashboard bot WhatsApp (scan QR)

```bash
sudo tailscale serve --bg --https=8443 8081
```

Buka `https://kjpp-nas.nama-tailnet.ts.net:8443/manager`, lalu ikuti [`bot-whatsapp.md`](bot-whatsapp.md).
Setelah WhatsApp tersambung, port ini boleh dimatikan: `sudo tailscale serve --https=8443 off`.

> **Jangan pakai `tailscale funnel`.** Funnel membuka aplikasi ke internet publik. Serve hanya untuk
> perangkat di tailnet.

---

## Bagian H — Hubungkan perangkat rekan kantor

### H1. Pasang Tailscale di tiap perangkat

- **Windows / macOS:** https://tailscale.com/download → install → login.
- **Android / iPhone:** Play Store / App Store → **Tailscale** → login → izinkan VPN.
- Pastikan tombol Tailscale **aktif (Connected)** setiap mau membuka aplikasi.

### H2. Beri akses — pilih cara

| Cara | Langkah | Cocok untuk |
|---|---|---|
| **Undang sebagai user tailnet** | Admin Console → **Users → Invite users** → masukkan email rekan | Pegawai tetap; tiap orang punya akun sendiri |
| **Share perangkat NAS saja** | Machines → `kjpp-nas` → ⋯ → **Share** → kirim tautan | Orang luar/sementara; mereka hanya melihat NAS, bukan perangkat lain |

Batas jumlah user & perangkat tergantung paket Tailscale — cek halaman harga Tailscale sebelum
mengundang banyak orang.

### H3. (Disarankan) Batasi akses hanya ke aplikasi

Admin Console → **Access controls**. Contoh kebijakan: semua anggota hanya boleh ke port HTTPS NAS,
admin boleh semua (SSH, SMB, dashboard bot).

```json
{
  "tagOwners": { "tag:nas": ["autogroup:admin"] },
  "grants": [
    { "src": ["autogroup:member"], "dst": ["tag:nas"], "ip": ["tcp:443"] },
    { "src": ["autogroup:admin"],  "dst": ["*"],       "ip": ["*"] }
  ]
}
```

Lalu Machines → `kjpp-nas` → ⋯ → **Edit ACL tags** → tambahkan `tag:nas`.
Uji dengan akun non-admin sebelum dibagikan. Simpan salinan kebijakan lama sebelum mengganti.

---

## Bagian I — (Opsional) Hanya bisa diakses lewat Tailscale

Secara bawaan aplikasi juga bisa dibuka di kantor lewat `http://192.168.1.100:8080`. Kalau ingin
**semua** akses wajib lewat Tailscale, ubah `ports` di `docker-compose.yml`:

```yaml
  app:
    ports:
      - "127.0.0.1:8080:80"
  wa:
    ports:
      - "127.0.0.1:8081:8080"
```

```bash
sudo docker compose up -d app wa
```

Tailscale Serve tetap bisa meneruskan ke `127.0.0.1`. Konsekuensi: perangkat di kantor pun harus
menyalakan Tailscale.

---

## Bagian J — Uji setelah pemasangan

Dari perangkat yang **tidak** di jaringan kantor (misal HP dengan data seluler + Tailscale aktif):

- [ ] `https://kjpp-nas.nama-tailnet.ts.net` terbuka, ikon gembok tampil (tanpa peringatan sertifikat)
- [ ] Tampilan lengkap (warna, tombol) — bukan halaman polos tanpa CSS
- [ ] Login → Beranda tampil, foto profil & logo muncul
- [ ] List Project → data dari laptop ada
- [ ] Unduh Word & **Cetak PDF** proposal (pertama kali 10–20 detik)
- [ ] Invoice, Kwitansi, Surat Tugas (font Arial Narrow)
- [ ] Export Excel
- [ ] Upload barcode Surat Tugas & ganti foto profil
- [ ] Submit review nilai → pesan WhatsApp masuk grup, tautannya membuka alamat `ts.net`
- [ ] Matikan Tailscale di HP → aplikasi **tidak** bisa dibuka (akses benar-benar tertutup)

---

## Bagian K — Supaya akses cepat

Tailscale berusaha menyambungkan perangkat **langsung**. Kalau gagal, lalu lintas lewat server relay
(DERP) — tetap aman tapi terasa lambat.

Cek dari laptop:

```powershell
tailscale ping kjpp-nas
tailscale netcheck
```

- `pong from kjpp-nas (100.x.y.z) via 203.0.113.10:41641` → **langsung** (bagus).
- `via DERP(sin)` → lewat relay. Perbaikan:
  1. Di router kantor aktifkan **UPnP / NAT-PMP**, atau buat port forwarding **UDP 41641** ke IP LAN NAS.
  2. Pastikan firewall tidak memblokir **UDP keluar**.
  3. Perbarui Tailscale di NAS & perangkat ke versi terbaru.
  4. Jaringan seluler/hotspot tertentu memang memaksa relay — bandingkan dengan Wi-Fi lain.

Aplikasi sendiri sudah dibuat ringan untuk jaringan lambat: Tailwind disimpan lokal, OPcache aktif,
foto profil dikompres ≤ ratusan KB.

---

## Bagian L — Pemakaian rutin

### Update aplikasi

```bash
cd /volume1/docker/kjpp-app
# salin file yang berubah lewat \\kjpp-nas\docker\kjpp-app  (JANGAN timpa .env, storage-data, mysql-data, tailscale-state)
sudo docker compose up -d --build app
```

Wajib `--build` — OPcache produksi tidak membaca ulang file PHP yang berubah.

### Backup database (rutin, misal harian)

```bash
cd /volume1/docker/kjpp-app
sudo docker exec kjpp-db sh -c 'exec mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" --single-transaction "$MYSQL_DATABASE"' > backup-$(date +%F).sql
```

Simpan salinan di luar NAS. Folder penting: `mysql-data`, `storage-data`, `tailscale-state`, file `.env`.

### Update Tailscale

- Opsi 1: App Center → Tailscale → Update.
- Opsi 2: `sudo docker compose --profile tailscale pull tailscale && sudo docker compose --profile tailscale up -d tailscale`

### Perintah berguna

| Tujuan | Perintah |
|---|---|
| Status container | `sudo docker compose ps` |
| Log aplikasi | `sudo docker compose logs --tail=100 app` |
| Restart aplikasi | `sudo docker compose restart app` |
| Status Tailscale | `sudo tailscale status` |
| Status Serve | `sudo tailscale serve status` |
| Matikan Serve | `sudo tailscale serve reset` |
| Log Laravel | `tail -n 50 storage-data/logs/laravel.log` |

---

## Bagian M — Masalah umum

| Gejala | Penyebab & solusi |
|---|---|
| MySQL: `Database is uninitialized and password option is not specified` | File `.env` tidak terbaca compose. Cek `ls -la | grep env` (jangan sampai bernama `.env.txt`), pastikan `.env` sejajar `docker-compose.yml` dan `DB_ROOT_PASSWORD`/`DB_PASSWORD` terisi. Perbaiki, lalu `sudo docker compose down` → hapus folder `mysql-data` yang masih kosong → `sudo docker compose up -d db` |
| `pull access denied for kjpp-app` / diminta `docker login` | Image aplikasi **dibangun sendiri**, tidak ada di Docker Hub. Deploy lewat menu Project sebelum image dibuat → lewat SSH: `sudo docker compose build app` lalu `sudo docker compose up -d` |
| Alamat `ts.net` tidak terbuka sama sekali | Tailscale di perangkat belum **Connected**, atau akun belum diundang/di-share (Bagian H) |
| `tailscale serve` menolak: HTTPS belum aktif | Aktifkan **HTTPS Certificates** di Admin Console → DNS (Bagian B2) |
| Peringatan sertifikat / lama sekali saat pertama buka | Sertifikat sedang dibuat; tunggu ±1 menit lalu muat ulang |
| Halaman tampil polos tanpa CSS lewat HTTPS | Kode di NAS belum berisi `trustProxies` di `bootstrap/app.php` → salin versi terbaru → `sudo docker compose up -d --build app` |
| Link di WhatsApp mengarah ke IP lama | `APP_URL` di `.env` belum diganti → ubah → `sudo docker compose restart app` |
| 502 Bad Gateway | Container `kjpp-app` belum jalan → `sudo docker compose ps` & `logs app`; cek `curl -I http://127.0.0.1:8080/login` |
| Container `kjpp-tailscale` gagal: `/dev/net/tun` tidak ada | Ganti `TS_USERSPACE: "true"` di service tailscale → `sudo docker compose --profile tailscale up -d tailscale` (Serve tetap berfungsi) |
| NAS tiba-tiba hilang dari tailnet setelah berbulan-bulan | Key expiry belum dimatikan (Bagian C-akhir) → login ulang lalu **Disable key expiry** |
| Akses terasa lambat | Koneksi lewat relay DERP (Bagian K) |
| "419 Page Expired" / semua user logout | `APP_KEY` beda dengan laptop (Bagian D2) |
| Tidak bisa konek DB (`SQLSTATE[HY000] [2002]`) | `kjpp-db` belum healthy, atau password `.env` diubah setelah `mysql-data` dibuat |
| Perubahan kode tidak muncul | Wajib `sudo docker compose up -d --build app` |

---

## Bagian N — Keamanan

- Matikan **SSH** NAS setelah selesai; nyalakan hanya saat perlu (SSH lewat Tailscale tetap bisa bila dinyalakan).
- **Jangan** buka port 8080/8081 di router dan **jangan** pakai `tailscale funnel`.
- Saat pegawai keluar: Admin Console → **Users** → hapus/suspend akunnya, dan **Machines** → hapus perangkatnya.
  Nonaktifkan juga akunnya di aplikasi (Kelola Pengguna).
- Aktifkan autentikasi dua langkah pada akun pemilik tailnet.
- `.env` & auth key berisi rahasia — jangan dibagikan, jangan di-commit.
- Ganti password akun admin aplikasi yang masih bawaan.

---

## Catatan

- File deploy (`Dockerfile`, `docker-compose.yml`, `docker/*`) **belum pernah diuji build** di NAS
  (laptop tidak memakai Docker). Catat pesan error bila build/start gagal.
- Nama menu UGOS Pro dan Tailscale Admin Console bisa sedikit berbeda antar versi.
- Perubahan kode terkait tutorial ini (15 Sep 2026): `trustProxies` di `bootstrap/app.php`,
  service `tailscale` (profile) di `docker-compose.yml`, variabel `TS_*` di `docker/env.nas.example`.
