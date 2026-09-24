{{-- Panduan singkat per peran, satu halaman A4 — bahan presentasi internal
     (2026-09-24, permintaan user). Sengaja tidak memakai data proyek: isinya
     aturan aplikasi, jadi aman dibagikan. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 26px 30px; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 9.6px; color: #111; line-height: 1.38; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }

        .head { border-bottom: 2px solid #001F60; padding-bottom: 6px; margin-bottom: 9px; }
        .head h1 { margin: 0; font-size: 17px; color: #001F60; letter-spacing: .3px; }
        .head p { margin: 3px 0 0; font-size: 9.4px; color: #555; }

        h2 { margin: 0 0 5px; font-size: 10.4px; color: #001F60; text-transform: uppercase; letter-spacing: .4px; }

        .role { border: 1px solid #c9d2e4; border-radius: 3px; margin-bottom: 7px; }
        .role .bar { background: #001F60; color: #fff; font-weight: bold; padding: 4px 8px; font-size: 10px; }
        .role .body { padding: 6px 8px 7px; }
        .role .who { color: #5b6478; font-style: italic; margin: 0 0 3px; }
        ul { margin: 0; padding-left: 12px; }
        li { margin-bottom: 1.5px; }

        .flow td { padding: 4px 5px; border: 1px solid #c9d2e4; }
        .flow .n { background: #001F60; color: #fff; text-align: center; font-weight: bold; width: 14px; }
        .flow .st { font-weight: bold; white-space: nowrap; }
        .flow .who { color: #5b6478; white-space: nowrap; }

        .note { border: 1px solid #9aa4b8; border-radius: 3px; padding: 5px 7px; color: #40485c; }
        .note b { color: #001F60; }
        .foot { margin-top: 9px; text-align: right; font-size: 8px; color: #8a91a0; }
        .gap { width: 10px; }
    </style>
</head>
<body>

<div class="head">
    <h1>Panduan Pemakaian per Peran &mdash; Aplikasi Internal KJPP SPR</h1>
    <p>Siapa mengerjakan apa, dari proposal masuk sampai proyek selesai. Hak akses diatur di Pengaturan Sistem &gt; Kelola Role.</p>
</div>

<table>
    <tr>
        {{-- ============ KOLOM KIRI: PERAN ============ --}}
        <td style="width: 49%;">
            <h2>Peran Sistem &amp; Tugasnya</h2>

            <div class="role">
                <div class="bar">Administrator</div>
                <div class="body">
                    <p class="who">Pemilik sistem &mdash; akses penuh tanpa kecuali.</p>
                    <ul>
                        <li>Membuka semua menu dan menekan semua tombol alur kerja.</li>
                        <li>Satu-satunya yang boleh mengedit proyek yang sudah Selesai.</li>
                        <li>Satu-satunya yang boleh mengubah username pengguna.</li>
                        <li>Mengelola Role, Pengguna, Rekening Bank, Bot WhatsApp, dan Sampah.</li>
                    </ul>
                </div>
            </div>

            <div class="role">
                <div class="bar">General Admin</div>
                <div class="body">
                    <p class="who">Administrasi umum, keuangan, dan pengaturan sistem.</p>
                    <ul>
                        <li>Membuat &amp; mengedit proposal, mencetak PDF dan Word.</li>
                        <li>Menerbitkan invoice, menandai lunas, membatalkan, mencetak kwitansi.</li>
                        <li>Mengisi nomor Faktur Pajak, Surat Tugas, dan Laporan Final.</li>
                        <li>Mengelola klien, pengguna, role, dan rekening bank.</li>
                        <li>Menekan tombol <b>Buku Ditandatangani</b> dan membuat Tanda Terima.</li>
                    </ul>
                </div>
            </div>

            <div class="role">
                <div class="bar">Admin Produksi</div>
                <div class="body">
                    <p class="who">Penjaga alur produksi laporan.</p>
                    <ul>
                        <li>Membuat &amp; mengedit proposal, mengatur penilai dan jadwal survei.</li>
                        <li>Menekan <b>Konfirmasi Draft</b>, <b>Buku Dicetak</b>, dan <b>Resume Disetujui</b>.</li>
                        <li>Mengisi Nomor &amp; Tanggal Laporan Final sebelum buku dicetak.</li>
                        <li>Melihat invoice, tetapi tidak bisa menerbitkan atau melunasinya.</li>
                    </ul>
                </div>
            </div>

            <div class="role">
                <div class="bar">Surveyor</div>
                <div class="body">
                    <p class="who">Pelaksana lapangan dan penyusun draft.</p>
                    <ul>
                        <li>Mengisi data survei: penilai, tanggal mulai &amp; selesai per objek.</li>
                        <li>Mencetak Surat Tugas.</li>
                        <li>Menekan <b>Submit Review</b> setelah nilai siap.</li>
                        <li>Menekan <b>Draft Narasi Dibuat</b> setelah draft laporan selesai.</li>
                        <li>Tidak bisa membuat proposal maupun menyentuh invoice.</li>
                    </ul>
                </div>
            </div>

            <h2 style="margin-top: 7px;">Jabatan pada Biodata</h2>
            <div class="note">
                Selain Role, tombol alur kerja juga dibuka oleh <b>Jabatan</b> di biodata pengguna:
                <b>Reviewer</b> (Release Resume, Resume Banding, Telah Direview, Kembalikan ke Surveyor),
                <b>Penilai</b> dan <b>Pelaksana Inspeksi</b> (muncul di daftar penilai lapangan dan kartu Progress Status Penilai),
                <b>Penanggung Jawab</b> (nama penanda tangan proposal), <b>Admin</b> (tugas administrasi).
                Jadi seseorang bisa ber-Role Surveyor tetapi berjabatan Reviewer, dan tombolnya menyesuaikan.
            </div>
        </td>

        <td class="gap"></td>

        {{-- ============ KOLOM KANAN: ALUR ============ --}}
        <td style="width: 49%;">
            <h2>Alur Status Proyek</h2>
            <table class="flow">
                @foreach ([
                    ['Draft Proposal', 'General Admin / Admin Produksi', 'Proposal dibuat, objek &amp; biaya diisi, PDF dikirim ke klien.'],
                    ['Menunggu Persetujuan Klien', 'General Admin / Admin Produksi', 'Menunggu balasan klien atas proposal.'],
                    ['DP Invoicing', 'General Admin', 'Invoice DP terbit. Skema Bayar Nanti boleh langsung kerja.'],
                    ['In-Progress / Scheduled', 'Surveyor', 'Survei lapangan, penilaian, Submit Review, lalu Draft Narasi Dibuat.'],
                    ['Finalisasi', 'Reviewer &amp; Admin Produksi', 'Resume disetujui, draft dikonfirmasi &amp; direview, buku dicetak.'],
                    ['Tanda Tangan', 'Penanggung Jawab', 'Buku ditandatangani.'],
                    ['Pengiriman Buku', 'General Admin', 'Tanda Terima dibuat, buku dikirim ke klien.'],
                    ['Selesai / Selesai - Belum Lunas', '&mdash;', 'Otomatis: Selesai bila lunas, Belum Lunas bila masih ada tagihan.'],
                ] as $i => [$status, $who, $desc])
                    <tr>
                        <td class="n">{{ $i + 1 }}</td>
                        <td>
                            <span class="st">{!! $status !!}</span><br>
                            <span class="who">{!! $who !!}</span><br>
                            {!! $desc !!}
                        </td>
                    </tr>
                @endforeach
            </table>

            <h2 style="margin-top: 7px;">Aturan yang Sering Ditanyakan</h2>
            <div class="note">
                <ul style="padding-left: 11px;">
                    <li><b>SLA Draft</b> mulai dihitung satu hari kerja setelah tanggal survei terakhir; <b>SLA Final</b> berhenti saat buku dicetak.</li>
                    <li><b>Tanggal Penilaian</b> boleh diisi manual. Dikosongkan = memakai tanggal survei paling akhir.</li>
                    <li><b>Termin pembayaran</b> bisa 1, 2, atau 3 tahap, diisi lewat persen atau langsung nominal rupiah, dan masih bisa diubah sampai proyek masuk tahap pencetakan buku.</li>
                    <li><b>Skema pembayaran</b> terkunci begitu proyek lewat tahap Menunggu Persetujuan.</li>
                    <li><b>Invoice baru</b> ditolak bila seluruh nilai proyek sudah tertagih.</li>
                    <li><b>Login memakai username</b>, bukan email. Username hanya bisa diubah Administrator.</li>
                    <li><b>Proyek dibatalkan</b> masuk Sampah dan bisa dipulihkan lengkap dengan statusnya.</li>
                    <li><b>Nomor dokumen</b> (invoice, kwitansi, tanda terima) dibuat otomatis dan tidak dipakai ulang.</li>
                </ul>
            </div>

            <h2 style="margin-top: 7px;">Dokumen yang Dihasilkan</h2>
            <div class="note">
                Proposal, Surat Representasi, Surat Tugas, Invoice, Kwitansi, dan Tanda Terima Pengiriman Buku.
                Semuanya tersedia dalam <b>PDF</b> dan <b>Word</b>, memakai penomoran serta kop resmi kantor.
            </div>
        </td>
    </tr>
</table>

<div class="foot">Dicetak {{ now()->translatedFormat('d F Y') }} &middot; Aplikasi Internal KJPP Sugianto Prasodjo dan Rekan</div>

</body>
</html>
