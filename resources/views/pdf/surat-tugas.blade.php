<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 100px 60px 90px 60px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; }
        h1 { font-size: 14px; text-align: center; text-decoration: underline; margin-bottom: 4px; }
        h2 { font-size: 12px; margin-top: 18px; margin-bottom: 6px; border-bottom: 1px solid #333; padding-bottom: 3px; }
        .text-center { text-align: center; }
        .small { font-size: 9.5px; color: #333; }
        .label-col { width: 190px; vertical-align: top; padding: 4px 0; }
        .val-col { vertical-align: top; padding: 4px 0; }
    </style>
</head>
<body>

    {{-- Kop Surat --}}
    <table style="margin-bottom: 15px;">
        <tr>
            <td style="width: 90px;">
                <img src="{{ config('kjpp.company_logo') }}" style="width: 80px;" alt="Logo">
            </td>
            <td class="text-center">
                <div style="font-size: 15px; font-weight: bold;">{{ config('kjpp.company_name') }}</div>
                <div class="small">{{ config('kjpp.company_address') }}</div>
                <div class="small">Telp: {{ config('kjpp.company_phone') }} | Email: {{ config('kjpp.company_email') }}</div>
            </td>
            <td style="width: 90px;"></td>
        </tr>
    </table>
    <hr style="border-top: 2px solid #1a1a1a;">

    <h1 class="mt-20" style="margin-top: 25px;">SURAT TUGAS PENILAIAN</h1>
    <p class="text-center small">No. {{ $project->proposal_number }}/ST</p>

    <p style="margin-top: 20px;">
        Yang bertanda tangan di bawah ini, manajemen {{ config('kjpp.company_name') }}, dengan ini menugaskan
        Penilai Lapangan tersebut di bawah untuk melaksanakan survei/inspeksi lapangan atas
        {{ $project->valuationObjects->count() > 1 ? 'seluruh objek penilaian' : 'objek penilaian' }}
        sebagai berikut:
    </p>

    <table style="margin-top: 15px;">
        <tr>
            <td class="label-col">Nomor Proyek</td>
            <td class="val-col">: {{ $project->proposal_number }}</td>
        </tr>
        <tr>
            <td class="label-col">Nama Penilai Lapangan</td>
            <td class="val-col">: <strong>{{ $project->assigned_appraiser }}</strong></td>
        </tr>
        <tr>
            <td class="label-col">Tanggal Pelaksanaan Survei</td>
            <td class="val-col">: <strong>{{ \Carbon\Carbon::parse($project->survey_date)->translatedFormat('d F Y') }}</strong></td>
        </tr>
        <tr>
            <td class="label-col">Pemberi Tugas</td>
            <td class="val-col">: {{ $project->instructingClient->client_name }}</td>
        </tr>
        <tr>
            <td class="label-col">Estimasi Selesai Laporan</td>
            <td class="val-col">: {{ $project->estimated_completion_date_formatted ?? '-' }}</td>
        </tr>
    </table>

    {{-- ===================== RINCIAN OBJEK YANG DISURVEI ===================== --}}
    <h2>Rincian Objek yang Disurvei</h2>
    <table style="margin-top: 8px; border: 1px solid #333;">
        <tr style="background-color: #1a1a1a; color: #fff;">
            <td style="border: 1px solid #333; padding: 6px; width: 28px;" class="text-center small"><strong>No.</strong></td>
            <td style="border: 1px solid #333; padding: 6px; width: 160px;" class="small"><strong>Jenis Aset/Properti</strong></td>
            <td style="border: 1px solid #333; padding: 6px;" class="small"><strong>Lokasi</strong></td>
            <td style="border: 1px solid #333; padding: 6px; width: 130px;" class="small"><strong>Atas Nama</strong></td>
        </tr>
        @forelse ($project->valuationObjects as $object)
            <tr>
                <td style="border: 1px solid #333; padding: 6px; vertical-align: top;" class="text-center small">{{ $loop->iteration }}</td>
                <td style="border: 1px solid #333; padding: 6px; vertical-align: top;" class="small">
                    @foreach ($object->description_lines as $line)
                        {{ $line }}@if (!$loop->last)<br>@endif
                    @endforeach
                </td>
                <td style="border: 1px solid #333; padding: 6px; vertical-align: top;" class="small">{{ $object->location }}</td>
                <td style="border: 1px solid #333; padding: 6px; vertical-align: top;" class="text-center small">{{ $object->owner_name }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="border: 1px solid #333; padding: 6px;" class="small text-center">
                    {{ $project->asset_type }} — {{ $project->asset_address }}
                </td>
            </tr>
        @endforelse
    </table>

    <p style="margin-top: 20px;">
        Penilai Lapangan bertugas melakukan pengukuran, dokumentasi, dan pengumpulan data fisik maupun legal
        objek penilaian sesuai dengan Standar Penilaian Indonesia (SPI) dan Kode Etik Penilai Indonesia (KEPI)
        yang berlaku, untuk selanjutnya digunakan sebagai dasar penyusunan laporan penilaian.
    </p>

    <table style="margin-top: 50px;">
        <tr>
            <td style="width: 50%;"></td>
            <td style="width: 50%;">
                <div class="small">Jakarta, {{ now()->translatedFormat('d F Y') }}</div>
                <div class="small">{{ config('kjpp.company_name') }}</div>
                <div style="height: 60px;"></div>
                <div style="border-top: 1px solid #333; width: 200px;"></div>
                <div class="small"><strong>Arief Rachman Setiady, S.M., M.M., MAPPI (Cert.)</strong></div>
                <div class="small">Penilai Publik — Izin No. P-1.25.00690</div>
            </td>
        </tr>
    </table>

</body>
</html>
