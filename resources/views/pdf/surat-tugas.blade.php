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
        body { font-family: 'Arial Narrow', 'Helvetica', Arial, sans-serif; font-size: 12px; color: #1a1a1a; line-height: 1.38; }
        table { width: 100%; border-collapse: collapse; }
        .center { text-align: center; }
        .justify { text-align: justify; }
        .bold { font-weight: bold; }
        .italic { font-style: italic; }
        .underline { text-decoration: underline; }
        .kop-line { border-top: 2px solid #1a1a1a; margin: 17px 0 12px 0; }
        .label-col { width: 78px; vertical-align: top; }
        .colon-col { width: 10px; vertical-align: top; }
        .staff-table td { padding: 0 4px 1px 0; vertical-align: top; }
        .staff-no { width: 16px; vertical-align: top; }
        .staff-label { width: 78px; }
        .staff-colon { width: 10px; }

        /* Footer alamat kantor — berulang di setiap halaman (biasanya 1 halaman). */
        .pagefoot {
            position: fixed; bottom: -60px; left: 0; right: 0;
            font-size: 9.3px; color: #222; text-align: center; line-height: 1.4;
            border-top: 1px solid #000; padding-top: 4px;
        }
    </style>
</head>
<body>

    {{-- ===================== KOP: LOGO SPR-LONG, RATA TENGAH ===================== --}}
    <div class="center">
        <img src="{{ public_path('images/logo-spr-long.png') }}" style="width: 380px;">
    </div>
    <div class="kop-line"></div>

    {{-- ===================== NOMOR / PERIHAL (kiri) — JAKARTA, TANGGAL (kanan) ===================== --}}
    <table style="margin-bottom: 16px;">
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

    <p style="margin-bottom: 10px;">
        Kepada Yth,<br>
        <span class="bold">{{ mb_strtoupper($project->instructingClient->client_name) }}</span><br>
        {{ $project->instructingClient->address ?: '(alamat belum diisi pada data klien)' }}
    </p>

    <p>Dengan Hormat,</p>

    <p class="justify">
        Bersama ini kami menugaskan staff kami sebagai perwakilan {{ config('kjpp.company_name') }} untuk
        melakukan Penilaian Aset atas nama <span class="bold">{{ $project->instructingClient->client_name }}</span>
        dengan berdasarkan Surat Penawaran <span class="bold">No. {{ $project->proposal_number }} tanggal {{ $project->proposal_date?->translatedFormat('d F Y') }}</span>
        yang berupa:
    </p>

    <table style="margin: 4px 0 6px 0;">
        @foreach ($project->valuationObjects as $object)
            @php
                $descLines = $object->description_lines;
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

    <p style="margin-bottom: 2px;">
        dilaksanakan pada tanggal, {{ $project->survey_date ? \Carbon\Carbon::parse($project->survey_date)->translatedFormat('d F Y') : '' }}
    </p>
    <p style="margin-top: 2px; margin-bottom: 6px;">Adapun petugas kami adalah :</p>

    {{-- Indentasi 1 ruler (≈1cm) dari margin kiri, memisahkan daftar petugas dari teks di atasnya.
         Jumlah & jabatan petugas bebas per proyek (mis. 2 Penilai + 1 Reviewer, atau 1 Reviewer +
         1 Penilai + 1 Pelaksana Inspeksi) — dikelola lewat kartu Surat Tugas, urutan sesuai
         ditambahkan. --}}
    <table class="staff-table" style="margin: 0 0 10px 38px; width: 88%;">
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
                <td>{{ $staff->user->jabatan ?? '-' }}</td>
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

    <p style="margin-bottom: 2px;">Hormat kami,</p>
    <p class="bold" style="margin-top: 0; margin-bottom: 0;">{{ config('kjpp.company_name') }}</p>

    @if ($project->assignment_letter_barcode)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->path($project->assignment_letter_barcode) }}"
             style="width: 85px; height: 85px; margin: 4px 0;">
    @else
        <div style="height: 45px;"></div>
    @endif

    @php
        $signerName  = $project->signedBy->name ?? config('kjpp.signatory.name');
        $signerTitle = $project->signedBy->partner_status ?? config('kjpp.signatory.title');
    @endphp
    <p class="bold underline" style="margin-bottom: 0;">{{ $signerName }}, MAPPI (Cert.)</p>
    <p class="italic" style="margin-top: 0;">{{ $signerTitle }}</p>

    <p class="italic" style="margin-top: 8px; font-size: 8.5px;">
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
