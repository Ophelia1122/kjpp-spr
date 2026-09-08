<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 50px 60px; size: A4 landscape; }
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #1a1a1a; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .small { font-size: 10px; color: #333; }
        .frame { border: 2px solid #1a1a1a; padding: 25px 35px; }
        .label-col { width: 210px; vertical-align: top; padding: 6px 0; }
        .val-col { vertical-align: top; padding: 6px 0; border-bottom: 1px dotted #999; }
        .amount-box { border: 1px solid #333; padding: 10px 14px; font-weight: bold; font-size: 14px; }
    </style>
</head>
<body>

<div class="frame">

    {{-- ===================== KOP + JUDUL ===================== --}}
    <table style="margin-bottom: 15px;">
        <tr>
            <td style="width: 90px;">
                <img src="{{ config('kjpp.company_logo') }}" style="width: 75px;" alt="Logo">
            </td>
            <td>
                <div style="font-size: 16px; font-weight: bold;">{{ config('kjpp.company_name') }}</div>
                <div class="small">{{ config('kjpp.company_address') }}</div>
                <div class="small">Telp: {{ config('kjpp.company_phone') }} | Email: {{ config('kjpp.company_email') }}</div>
            </td>
            <td style="width: 220px; text-align: right; vertical-align: top;">
                <div style="font-size: 18px; font-weight: bold; letter-spacing: 1px;">KWITANSI</div>
                <div class="small">(Receipt)</div>
                <div class="small" style="margin-top: 6px;">No. {{ $invoice->invoice_number }}</div>
                 <div class="small">Tgl: {{ $invoice->payment_date->translatedFormat('d F Y') }}</div>
            </td>
        </tr>
    </table>
    <hr style="border-top: 2px solid #1a1a1a; margin-bottom: 15px;">

    {{-- ===================== ISI KWITANSI ===================== --}}
    <table style="margin-top: 10px;">
        <tr>
            <td class="label-col">Telah diterima dari <span class="small">(Received from)</span></td>
            <td class="val-col"><strong>{{ $project->instructingClient->client_name }}</strong></td>
        </tr>
        <tr>
            <td class="label-col">Uang sejumlah <span class="small">(The sum of)</span></td>
            <td class="val-col">
                <em>{{ \App\Helpers\Terbilang::make($invoice->amount) }}</em>
            </td>
        </tr>
        <tr>
            <td class="label-col">Untuk pembayaran <span class="small">(In payment of)</span></td>
            <td class="val-col">
                {{ $invoice->term_description ?? $invoice->invoice_type }}
                — Jasa Penilaian {{ $project->asset_summary_label }}, Proyek No. {{ $project->proposal_number }}
                @if ($invoice->invoice_type === 'DP')
                    (Uang Muka / Down Payment)
                @else
                    (Pelunasan Sisa Tagihan)
                @endif
            </td>
        </tr>
    </table>

    {{-- ===================== NOMINAL BOX ===================== --}}
    <table style="margin-top: 25px;">
        <tr>
            <td style="width: 60%;"></td>
            <td style="width: 40%;">
                <div class="amount-box text-center">
                    Rp {{ number_format($invoice->amount, 0, ',', '.') }},-
                </div>
            </td>
        </tr>
    </table>

    {{-- ===================== TANDA TANGAN ===================== --}}
    <table style="margin-top: 45px;">
        <tr>
            <td style="width: 50%;"></td>
            <td style="width: 50%; text-align: center;">
                <div class="small">Jakarta, {{ $invoice->payment_date->translatedFormat('d F Y') }}</div>
                <div class="small">{{ config('kjpp.company_name') }}</div>
                <div style="height: 65px;"></div>
                <div style="border-top: 1px solid #333; width: 220px; margin: 0 auto;"></div>
                <div class="small"><strong>Arief Rachman Setiady, S.M., M.M., MAPPI (Cert.)</strong></div>
                <div class="small">Penilai Publik — Izin No. P-1.25.00690</div>
            </td>
        </tr>
    </table>

</div>

</body>
</html>
