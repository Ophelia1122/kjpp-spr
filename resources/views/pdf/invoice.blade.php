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
        .text-right { text-align: right; }
        .small { font-size: 9.5px; color: #333; }
        .label-col { width: 170px; vertical-align: top; padding: 4px 0; }
        .val-col { vertical-align: top; padding: 4px 0; }
        .amount-table td { border: 1px solid #333; padding: 8px; }
        .status-paid { color: #16a34a; font-weight: bold; }
        .status-unpaid { color: #dc2626; font-weight: bold; }
        .bank-box { border: 1px solid #999; padding: 10px; margin-top: 10px; }
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

    <h1 style="margin-top: 25px;">
        INVOICE {{ $invoice->invoice_type === 'DP' ? 'UANG MUKA (DP)' : 'PELUNASAN' }}
    </h1>
    <p class="text-center small">No. {{ $invoice->invoice_number }}</p>

    {{-- Detail Klien & Invoice --}}
    <table style="margin-top: 20px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="small" style="font-weight: bold; margin-bottom: 4px;">Ditagihkan Kepada:</div>
                <div><strong>{{ $project->instructingClient->client_name }}</strong></div>
                <div class="small">{{ $project->instructingClient->address }}</div>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <table>
                    <tr>
                        <td class="label-col small">Nomor Invoice</td>
                        <td class="val-col small">: {{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="label-col small">Nomor Proyek</td>
                        <td class="val-col small">: {{ $project->proposal_number }}</td>
                    </tr>
                    <tr>
                        <td class="label-col small">Tanggal Terbit</td>
                        <td class="val-col small">: {{ $invoice->created_at->translatedFormat('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label-col small">Tipe Termin</td>
                        <td class="val-col small">: {{ $invoice->term_description ?? $invoice->invoice_type }}</td>
                    </tr>
                    <tr>
                        <td class="label-col small">Status Pembayaran</td>
                        <td class="val-col small">
                            :
                            <span class="{{ $invoice->status === 'Paid' ? 'status-paid' : 'status-unpaid' }}">
                                {{ $invoice->status === 'Paid' ? 'LUNAS' : 'BELUM DIBAYAR' }}
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Rincian Tagihan --}}
    <table class="amount-table" style="margin-top: 25px;">
        <tr style="background-color: #f0f0f0;">
            <td><strong>Uraian</strong></td>
            <td class="text-right" style="width: 160px;"><strong>Nominal (Rp)</strong></td>
        </tr>
        <tr>
            <td>
                Jasa Penilaian — {{ $project->asset_type }}<br>
                <span class="small">{{ $invoice->term_description ?? $invoice->invoice_type }}
                    untuk proyek {{ $project->proposal_number }}</span>
            </td>
            <td class="text-right">{{ number_format($invoice->amount, 0, ',', '.') }}</td>
        </tr>
        <tr style="background-color: #f0f0f0;">
            <td class="text-right"><strong>TOTAL TAGIHAN</strong></td>
            <td class="text-right"><strong>Rp {{ number_format($invoice->amount, 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <p class="small" style="margin-top: 8px;">
        Terbilang: <em>{{ \App\Helpers\Terbilang::make($invoice->amount) }}</em>
    </p>

    {{-- Informasi Rekening Bank --}}
    <div class="bank-box">
        <div class="small" style="font-weight: bold; margin-bottom: 4px;">Pembayaran dapat ditransfer ke rekening:</div>
        <table>
            <tr>
                <td class="label-col small">Bank</td>
                <td class="val-col small">: {{ $bank_info['bank_name'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label-col small">Nomor Rekening</td>
                <td class="val-col small">: {{ $bank_info['account_number'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label-col small">Atas Nama</td>
                <td class="val-col small">: {{ $bank_info['account_name'] ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <p class="small" style="margin-top: 15px;">
        Mohon konfirmasi pembayaran dikirimkan ke {{ config('kjpp.company_email') }} agar dapat kami proses
        lebih lanjut. Invoice ini sah tanpa tanda tangan basah, dicetak melalui sistem {{ config('kjpp.company_name') }}.
    </p>

    <table style="margin-top: 40px;">
        <tr>
            <td style="width: 50%;"></td>
            <td style="width: 50%;">
                <div class="small">Hormat kami,</div>
                <div class="small">Jakarta, {{ now()->translatedFormat('d F Y') }}</div>
                <div style="height: 60px;"></div>
                <div style="border-top: 1px solid #333; width: 200px;"></div>
                <div class="small"><strong>Arief Rachman Setiady, S.M., M.M, MAPPI (Cert.)<strong></div>
                <div class="small">Partner</div>
            </td>
        </tr>
    </table>

</body>
</html>
