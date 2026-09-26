<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @font-face { font-family: 'Arial Narrow'; font-weight: normal; font-style: normal; src: url("{{ public_path('fonts/arialn.ttf') }}") format("truetype"); }
        @font-face { font-family: 'Arial Narrow'; font-weight: bold;   font-style: normal; src: url("{{ public_path('fonts/arialnb.ttf') }}") format("truetype"); }
        @font-face { font-family: 'Arial Narrow'; font-weight: normal; font-style: italic; src: url("{{ public_path('fonts/arialni.ttf') }}") format("truetype"); }
        @font-face { font-family: 'Arial Narrow'; font-weight: bold;   font-style: italic; src: url("{{ public_path('fonts/arialnbi.ttf') }}") format("truetype"); }

        /* A4, margin Top 2,75cm / Kanan-Bawah-Kiri 2,54cm (96dpi: 1cm ≈ 37.8px). */
        @page { margin: 24px 72px 72px 72px; }
        /* Font 11pt & spasi lebih padat (2026-09-14, feedback user). Paragraf
           diberi margin kecil eksplisit — bawaan dompdf ±1 baris atas-bawah. */
        /* line-height 1.1: dompdf memperlebar baris sesuai metrik font TTF, jadi
           nilai kecil di sini sudah setara spasi 1,15 di Word. */
        body { font-family: 'Arial Narrow', 'Helvetica', Arial, sans-serif; font-size: 11pt; color: #1a1a1a; line-height: 1.1; }
        p { margin: 0 0 5px 0; }
        table { width: 100%; border-collapse: collapse; }
        .center { text-align: center; }
        .justify { text-align: justify; }
        .bold { font-weight: bold; }
        .italic { font-style: italic; }
        .underline { text-decoration: underline; }
        .kop-line { border-top: 2px solid #1a1a1a; margin: 10px 0 8px 0; }
        .label-col { width: 70px; vertical-align: top; }
        .colon-col { width: 10px; vertical-align: top; }
        .staff-table td { padding: 0 4px 0 0; vertical-align: top; }
        .staff-no { width: 16px; vertical-align: top; }
        .staff-label { width: 70px; }
        .staff-colon { width: 10px; }

        /* Footer alamat kantor — berulang di setiap halaman (biasanya 1 halaman). */
        .pagefoot {
            position: fixed; bottom: -60px; left: 0; right: 0;
            font-size: 9.3px; color: #222; text-align: center; line-height: 1.15;
            padding-top: 4px; /* garis atas footer dihapus (2026-09-14, feedback user) */
        }
    </style>
</head>
<body>

    {{-- ===================== KOP: LOGO SPR-LONG, RATA TENGAH ===================== --}}
    <div class="center">
        {{-- Logo kop 125% (380 -> 475px, 2026-09-14, feedback user). --}}
        <img src="{{ public_path('images/logo-spr-long.png') }}" style="width: 475px;">
    </div>
    <div class="kop-line"></div>

    {{-- ===================== NOMOR / PERIHAL (kiri) — JAKARTA, TANGGAL (kanan) ===================== --}}
    <table style="margin-bottom: 8px;">
        <tr>
            <td style="width: 58%; vertical-align: top;">
                <table>
                    <tr>
                        <td class="label-col">No.</td>
                        <td class="colon-col">:</td>
                        <td>{{ $project->assignment_letter_number ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Perihal</td>
                        <td class="colon-col">:</td>
                        <td class="underline">Surat Tugas</td>
                    </tr>
                </table>
            </td>
            <td style="width: 42%; vertical-align: top; text-align: right;">
                Jakarta, {{ $project->assignment_letter_date ? $project->assignment_letter_date->translatedFormat('d F Y') : '-' }}
            </td>
        </tr>
    </table>

    {{-- "Kepada Yth" dipilih di kartu Surat Tugas; alamat dipecah setelah koma
         tiap ±8 cm, seperti alamat Pemberi Tugas di proposal (2026-09-14). --}}
    @php
        $recipient      = $project->assignment_letter_recipient;
        $recipientLines = \App\Helpers\AddressFormatter::lines(optional($recipient)->address);
    @endphp
    <p style="margin-bottom: 6px;">
        Kepada Yth,<br>
        <span class="bold">{{ mb_strtoupper((string) optional($recipient)->client_name) }}</span><br>
        @forelse ($recipientLines as $line)
            {{ $line }}@unless ($loop->last)<br>@endunless
        @empty
            (alamat belum diisi pada data klien)
        @endforelse
    </p>

    <p>Dengan Hormat,</p>

    {{-- Kalimat pekerjaan berbeda untuk Jasa Konsultasi (2026-09-26): mengikuti
         Surat Tugas master kantor, mis. "melakukan Jasa Penyusunan Laporan Studi
         Kelayakan". Kalimatnya bisa disunting di Teks Baku Proposal. --}}
    @if ($project->isKonsultasi())
        <p class="justify">
            Bersama ini kami menugaskan staff kami sebagai perwakilan {{ config('kjpp.company_name') }} untuk
            {{ $project->kalimatTugasKonsultasi() }}
            @if ($project->assignment_letter_request_basis_text !== '')
                {{ $project->assignment_letter_request_basis_text }}
            @endif
            Berdasarkan Surat Penawaran <span class="bold">No. {{ $project->proposal_number }} tanggal {{ $project->proposal_date?->translatedFormat('d F Y') }}</span>.
        </p>

        @if (trim((string) $project->work_object_description) !== '')
            <p class="justify" style="margin: 0 0 5px 16px;">{{ $project->work_object_description }}</p>
        @endif
    @else
    <p class="justify">
        Bersama ini kami menugaskan staff kami sebagai perwakilan {{ config('kjpp.company_name') }} untuk
        {{-- "atas nama" dipilih di kartu Surat Tugas; Dasar Permintaan dicetak tepat
             setelah nama klien (2026-09-14, feedback user). --}}
        melakukan Penilaian Aset atas nama <span class="bold">{{ $project->assignment_letter_on_behalf_name }}</span>.
        @if ($project->assignment_letter_request_basis_text !== '')
            {{ $project->assignment_letter_request_basis_text }}
        @endif
        Berdasarkan Surat Penawaran <span class="bold">No. {{ $project->proposal_number }} tanggal {{ $project->proposal_date?->translatedFormat('d F Y') }}</span>
        yang berupa:
    </p>

    {{-- Rincian objek menjorok 1/3 tab (±0,42 cm = 16px) dari margin kiri
         (2026-09-14, feedback user — 1 tab penuh terlalu jauh). --}}
    <table style="margin: 0 0 5px 16px; width: 97%;">
        @foreach ($project->valuationObjects as $object)
            @php
                $descLines = $object->assignment_letter_description_lines;
                $descHead  = $descLines[0] ?? 'Aset';
                $descRest  = array_slice($descLines, 1);
            @endphp
            <tr>
                <td style="width: 18px; vertical-align: top;">{{ $loop->iteration }}.</td>
                <td class="justify">
                    <span class="bold italic">{{ $descHead }},</span>
                    @if (count($descRest))
                        {{ implode(', ', $descRest) }}
                    @endif
                    yang berlokasi di {{ $object->location }}
                </td>
            </tr>
        @endforeach
    </table>
    @endif

    <p style="margin-bottom: 2px;">
        {{ $project->isKonsultasi() ? 'Inspeksi akan dilaksanakan pada tanggal :' : 'dilaksanakan pada tanggal,' }} {{ $project->survey_date ? \Carbon\Carbon::parse($project->survey_date)->translatedFormat('d F Y') : '' }}
    </p>
    <p style="margin-bottom: 3px;">Adapun petugas kami adalah :</p>

    {{-- Indentasi 1 ruler (≈1cm) dari margin kiri, memisahkan daftar petugas dari teks di atasnya.
         Jumlah & jabatan petugas bebas per proyek (mis. 2 Penilai + 1 Reviewer, atau 1 Reviewer +
         1 Penilai + 1 Pelaksana Inspeksi) — dikelola lewat kartu Surat Tugas, urutan sesuai
         ditambahkan. --}}
    <table class="staff-table" style="margin: 0 0 6px 38px; width: 88%;">
        @foreach ($project->assignmentStaff as $staff)
            <tr>
                <td class="staff-no">{{ $loop->iteration }}</td>
                <td class="staff-label">Nama</td>
                <td class="staff-colon">:</td>
                <td>{{ $staff->user->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="staff-no"></td>
                <td class="staff-label">Jabatan</td>
                <td class="staff-colon">:</td>
                {{-- Posisi manual menang atas Jabatan biodata (2026-09-26). --}}
                <td>{{ $staff->position ?: ($staff->user->jabatan ?? '-') }}</td>
            </tr>
            <tr>
                <td class="staff-no"></td>
                <td class="staff-label">No. MAPPI</td>
                <td class="staff-colon">:</td>
                <td>{{ $staff->user->mappi_no ?? '-' }}</td>
            </tr>
        @endforeach
    </table>

    <p class="justify">
        Surat Tugas ini sekaligus merupakan Berita Acara Pemeriksaan lapangan, mohon membubuhkan tanda tangan
        setelah staff / petugas kami selesai melakukan tugasnya, Demikian atas perhatian dan kerjasamanya kami
        sampaikan banyak terima kasih.
    </p>

    <p style="margin-bottom: 0;">Hormat kami,</p>
    <p class="bold" style="margin-bottom: 0;">{{ config('kjpp.company_name') }}</p>

    {{-- Barcode sedikit lebih besar (85 -> 100px) & menempel langsung ke nama
         penandatangan: display:block menghilangkan celah baris di bawah gambar
         (2026-09-14, feedback user). --}}
    @if ($project->assignment_letter_barcode)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->path($project->assignment_letter_barcode) }}"
             style="display: block; width: 100px; height: 100px; margin: 3px 0 0 0;">
    @else
        <div style="height: 45px;"></div>
    @endif

    @php
        $signerName  = $project->signedBy->name ?? config('kjpp.signatory.name');
        $signerTitle = $project->signedBy->partner_status ?? config('kjpp.signatory.title');
    @endphp
    {{-- margin-top negatif: gambar barcode punya bingkai putih (quiet zone) sendiri,
         jadi nama ditarik ke atas supaya benar-benar menempel. --}}
    <p class="bold underline" style="margin: {{ $project->assignment_letter_barcode ? '-3px' : '0' }} 0 0 0;">{{ $signerName }}</p>
    <p class="italic" style="margin: 0;">{{ $signerTitle }}</p>

    {{-- Tulisan "Perhatian" 10pt (2026-09-14, feedback user). --}}
    <p class="italic" style="margin-top: 8px; font-size: 10pt;">
        <u> Perhatian </u> : Dilarang meminta atau menerima imbalan jasa dalam bentuk apapun diluar kontrak yang telah di setujui.
        Apabila petugas lapangan kami meminta sesuatu kepada pihak klien/nasabah maka mohon dilaporkan kepada kami.
    </p>

    <div class="pagefoot">
        @foreach (config('kjpp.footer.lines') as $line)
            {{ $line }}<br>
        @endforeach
    </div>

</body>
</html>
