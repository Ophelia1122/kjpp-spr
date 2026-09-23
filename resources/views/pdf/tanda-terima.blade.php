{{-- Tanda Terima Pengiriman Buku (2026-09-23, feedback user) — meniru
     dokumen kantor "214 - Tanda Terima_...". Butuh $receipt & $project. --}}
@php
    $recipient = $receipt->recipient;
    $name      = $recipient?->client_name ?: $project->effective_client_name;
    $address   = trim((string) ($recipient?->address ?? ''));
    $rows      = $receipt->documentRows();
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 36px 55px 60px 55px; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10.5px; color: #000; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; }
        .lh-logo { text-align: center; margin-bottom: 6px; }
        .lh-logo img { width: 475px; }
        hr.thick { border: none; border-top: 2.5px solid #000; margin: 4px 0 14px; }
        .doc-title { text-align: center; font-size: 17px; font-weight: bold; letter-spacing: 1px; margin: 2px 0 12px; }
        .bold { font-weight: bold; }
        .small { font-size: 9px; }
        .meta td { padding: 2px 0; vertical-align: top; }
        .doc-table { margin-top: 10px; }
        .doc-table th, .doc-table td { border: 1px solid #000; padding: 5px 7px; }
        .doc-table th { background: #e8eef7; font-weight: bold; text-align: center; }
        .text-center { text-align: center; }
        .sign { margin-top: 26px; }
        .sign td { vertical-align: top; }
        .note { margin-top: 12px; text-align: justify; }
        .warn { margin-top: 26px; font-size: 9.5px; }
    </style>
</head>
<body>
    <div class="lh-logo">
        <img src="{{ public_path('images/logo-spr-long.png') }}" alt="KJPP Sugianto Prasodjo dan Rekan">
    </div>
    <hr class="thick">

    <div class="doc-title">TANDA TERIMA</div>

    <table class="meta">
        <tr>
            <td style="width: 52%;">
                <span class="bold">Nomor Pengiriman</span><br>
                {{ $receipt->number }}
            </td>
            <td>
                <span class="bold">Tanggal Pengiriman</span><br>
                {{ $receipt->delivery_date->translatedFormat('d F Y') }}
            </td>
        </tr>
        <tr><td colspan="2" style="height: 8px;"></td></tr>
        <tr>
            <td>
                <span class="bold">Penerima:</span><br>
                <span class="bold">{{ $name }}</span><br>
                @foreach (preg_split('/\r\n|\r|\n/', $address) as $line)
                    @if (trim($line) !== '') {{ trim($line) }}<br> @endif
                @endforeach
                Up: {{ $receipt->recipient_up ?: '-' }}
            </td>
            <td>
                <span class="bold">Pengirim:</span><br>
                {{ config('kjpp.company_name') }}<br>
                {{ \Illuminate\Support\Str::after(config('kjpp.footer.lines.0'), 'Head Office: ') }}
            </td>
        </tr>
    </table>

    <p style="margin-top: 14px;">Telah diterima beberapa dokumen Penilaian Aset dengan rincian sebagai berikut:</p>

    <table class="doc-table">
        <thead>
            <tr>
                <th style="width: 6%;">No.</th>
                <th>Nama Dokumen</th>
                <th style="width: 18%;">Qty</th>
                <th style="width: 14%;">Jenis</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $row)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $row['label'] }}</td>
                    <td class="text-center">{{ $row['qty'] }} {{ $row['unit'] }}</td>
                    <td class="text-center">{{ $row['kind'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($receipt->note)
        <div class="note"><span class="bold">Keterangan:</span> {{ $receipt->note }}</div>
    @endif

    <table class="sign">
        <tr>
            <td style="width: 55%;">&nbsp;</td>
            <td>
                Diterima Oleh,<br><br><br><br>
                ( _______________________ )
            </td>
        </tr>
    </table>

    <div class="warn">
        <span class="bold">Perhatian.</span><br>
        Mohon sertakan nama jelas penerima dan tanggal penerimaan berkas. Terima kasih.
    </div>
</body>
</html>
