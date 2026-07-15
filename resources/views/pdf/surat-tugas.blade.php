<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 100px 60px 90px 60px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; }
        h1 { font-size: 14px; text-align: center; text-decoration: underline; margin-bottom: 4px; }
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
        Bersama ini kami menugaskan staff kami sebagai perwakilan KJPP Sugianto Prasodjo dan Rekan untuk melakukan Penilaian Aset atas nama
        {{$project->instructingClient->client_name}} dengan berdasarkan Surat Penawaran {{$project->proposal_number}}
        Yang bertanda tangan di bawah ini, manajemen {{ config('kjpp.company_name') }}, dengan ini menugaskan
        Penilai Lapangan tersebut di bawah untuk melaksanakan survei/inspeksi lapangan atas objek penilaian
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
            <td class="label-col">Jenis Objek Penilaian</td>
            <td class="val-col">: {{ $project->asset_type }}</td>
        </tr>
        <tr>
            <td class="label-col">Lokasi Objek</td>
            <td class="val-col">: {{ $project->asset_address }}</td>
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
