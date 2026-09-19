# Bot Notifikasi WhatsApp (Evolution API)

Bot mengirim pesan ke grup WhatsApp kantor:

| Pemicu | Yang di-mention |
|---|---|
| Surveyor klik **Ajukan Review** | Reviewer di Surat Tugas proyek (bila tidak ada: semua Reviewer aktif) |
| Reviewer klik **Kembalikan ke Surveyor** | Pengguna yang mengajukan review |

Pesan dikirim setelah halaman selesai diproses; bila bot mati, tombol tetap berjalan normal (kegagalan dicatat di `storage/logs/laravel.log`).

## 1. Siapkan nomor

1. Beli kartu perdana khusus bot, pasang di HP cadangan, daftar **WhatsApp Business** (nama profil mis. "Bot KJPP SPR").
2. Masukkan nomor bot ke grup WhatsApp kantor.
3. Isi pulsa berkala supaya nomor tidak hangus. Buka WhatsApp di HP itu sesekali (minimal tiap 2 minggu).

## 2. Jalankan container (NAS)

1. Di `.env` NAS isi `WA_BOT_API_KEY` (string acak panjang) dan `WA_DB_PASSWORD`.
2. `docker compose up -d` — container `kjpp-wa` & `kjpp-wa-db` ikut berjalan.
3. Buka `http://IP-NAS:8081/manager`, login dengan API key tadi.
4. **Create instance** bernama `kjpp` (integrasi *Baileys*), lalu **scan QR** dari WhatsApp HP bot
   (Perangkat Tertaut > Tautkan Perangkat).

## 2b. Uji di laptop (opsional)

Butuh Docker Desktop. Di folder proyek:

```
docker compose up -d wa wa-db
```

Isi `WA_BOT_API_KEY` & `WA_DB_PASSWORD` di `.env` laptop dulu. Dashboard: `http://localhost:8081/manager`.
URL di aplikasi: `http://localhost:8081`.

## 3. Hubungkan aplikasi

1. Login sebagai Administrator > **Pengaturan Sistem > Bot WhatsApp**.
2. Isi URL (`http://kjpp-wa:8080` di NAS), API key, instance `kjpp` > **Simpan**.
3. **Ambil daftar grup** > pilih grup kantor > centang **Aktifkan** > **Simpan**.
4. **Kirim Pesan Tes**.
5. Isi **Nomor WhatsApp** tiap Reviewer & Surveyor (Kelola Pengguna atau Profil Saya) supaya bisa di-mention.

## Perawatan

- Bila bot terlogout: buka dashboard, scan QR ulang. Sesi tersimpan di folder `wa-instances` & `wa-db-data`.
- Update versi: ganti tag image `evoapicloud/evolution-api` di `docker-compose.yml`, lalu `docker compose up -d wa`.
- Risiko: cara ini tidak resmi dari WhatsApp; nomor bisa diblokir bila dipakai berlebihan. Bot hanya
  mengirim beberapa pesan per hari ke grup internal, jadi risikonya kecil.
