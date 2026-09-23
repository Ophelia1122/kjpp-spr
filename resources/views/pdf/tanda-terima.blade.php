{{-- Tanda Terima Pengiriman Buku — tata letak & warna mengikuti contoh kantor
     "214 - Tanda Terima_..." (2026-09-23, feedback user). Butuh $receipt & $project. --}}
@php
    $recipient   = $receipt->recipient;
    $name        = $recipient?->client_name ?: $project->effective_client_name;
    $addressLines = collect(preg_split('/\r\n|\r|\n/', (string) ($recipient?->address ?? '')))
        ->map(fn ($l) => trim($l))->filter()->values();
    $rows        = $receipt->documentRows();
    $up          = $receipt->recipient_up ?: '-';
    $note        = $receipt->note;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 30px 62px; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 11px; color: #000; line-height: 1.2; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .justify { text-align: justify; }

        .box { border: 1px solid #001F60; padding: 8px 10px 10px; }
        .navy-bar { background: #001F60; color: #fff; font-weight: bold; padding: 4px 8px; }
        .logo { width: 300px; margin: 0 0 7px 2px; }

        .meta td { padding: 1px 0; }
        .meta .label { width: 122px; }

        .docs { margin-top: 8px; }
        .docs th { background: #001F60; color: #fff; font-weight: bold; padding: 2px 6px; text-align: left; }
        .docs th.c, .docs td.c { text-align: center; }
        .docs td { padding: 1px 6px; }

        .note-box { border: 1px solid #9aa4b8; padding: 4px 7px; color: #5b6478; font-style: italic; font-size: 9.5px; }
        .slip { margin-top: 10px; border: 1px solid #001F60; }
        .slip .pad { padding: 3px 8px; }
        .slip .foot { background: #001F60; height: 14px; }
    </style>
</head>
<body>

<div class="box">
    <img class="logo" src="{{ public_path('images/logo-spr-short.png') }}" alt="KJPP Sugianto Prasodjo dan Rekan">

    <div class="navy-bar center" style="letter-spacing: .5px;">TANDA TERIMA</div>

    <table class="meta" style="margin-top: 8px;">
        <tr>
            <td style="width: 50%;">
                <table class="meta">
                    <tr><td class="label bold">Nomor Pengiriman</td><td>{{ $receipt->number }}</td></tr>
                    <tr><td class="label bold">Tanggal Pengiriman</td><td>{{ $receipt->delivery_date->translatedFormat('d F Y') }}</td></tr>
                    <tr><td colspan="2" style="height: 7px;"></td></tr>
                    <tr><td colspan="2" class="bold">Pengirim:</td></tr>
                    <tr><td colspan="2">KJPP Sugianto Prasodjo dan Rekan</td></tr>
                </table>
            </td>
            <td>
                <p class="bold" style="margin: 0;">Penerima:</p>
                <p class="bold" style="margin: 2px 0 0;">{{ $name }}</p>
                @foreach ($addressLines as $line)
                    <p style="margin: 0;">{{ $line }}</p>
                @endforeach
                <table class="meta" style="margin-top: 2px;">
                    <tr><td style="width: 34px;" class="bold">Up:</td><td>{{ $up }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <p class="bold" style="margin: 9px 0 4px;">Telah diterima beberapa dokumen Penilaian Aset dengan rincian sebagai berikut:</p>

    @if ($note)
        <table>
            <tr>
                <td style="width: 26px;">-</td>
                <td class="justify">{{ $note }}</td>
            </tr>
        </table>
    @endif

    <table class="docs">
        <thead>
            <tr>
                <th style="width: 26px;">No.</th>
                <th>Nama Dokumen</th>
                <th class="c" style="width: 52px;">&nbsp;</th>
                <th style="width: 92px;">Qty</th>
                <th style="width: 92px;">Jenis</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $row)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $row['label'] }}</td>
                    <td class="c" style="font-family: 'DejaVu Sans', sans-serif;">&#9745;</td>
                    <td>{{ $row['qty'] }} {{ $row['unit'] }}</td>
                    <td>{{ $row['kind'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top: 9px;">
        <tr>
            <td style="width: 55%;">
                <p class="bold" style="margin: 0 0 34px 22px;">Diterima Oleh,</p>
                <p style="margin: 0 0 0 8px;">( ________________ )</p>
            </td>
            <td style="padding-top: 18px;">
                <div class="note-box">
                    Perhatian.<br>
                    Mohon sertakan nama jelas penerima dan tanggal penerimaan berkas.<br>
                    Terima kasih.
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- Potongan "Kepada Yth." untuk ditempel di amplop. --}}
<div class="slip">
    <div class="pad bold" style="border-bottom: 1px solid #001F60;">Kepada Yth.</div>
    <div class="navy-bar center" style="font-size: 13px;">{{ mb_strtoupper($name) }}</div>
    <div class="pad">
        @foreach ($addressLines as $line)
            <p style="margin: 0;">{{ $line }}</p>
        @endforeach
        <table class="meta" style="margin-top: 2px;">
            <tr><td style="width: 34px;" class="bold">Up:</td><td>{{ $up }}</td></tr>
        </table>
        @if ($note)
            <table style="margin-top: 2px;">
                <tr>
                    <td style="width: 74px;" class="bold">Keterangan:</td>
                    <td class="justify">{{ $note }}</td>
                </tr>
            </table>
        @endif
    </div>
    <div class="foot"></div>
</div>

</body>
</html>
