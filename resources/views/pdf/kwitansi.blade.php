<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 55px 55px 75px 55px; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10.5px; color: #000; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .italic { font-style: italic; }
        .underline { text-decoration: underline; }
        .small { font-size: 9px; }

        .box { border: 1.3px solid #000; padding: 8px 10px; }
        .doc-title { font-size: 20px; font-weight: bold; text-align: center; }
        .doc-sub { font-size: 13px; font-style: italic; text-align: center; }

        .frame { border: 1.3px solid #000; margin-top: 12px; }
        .frame td { padding: 8px 10px; vertical-align: top; }
        .label-col { width: 160px; }
        .dots { border-bottom: 1px dotted #999; }
        .dots-row td { border-bottom: 1px dotted #999; }
        table.breakdown td { padding: 1px 4px 1px 0; }

        .amount-row { border-top: 1.3px solid #000; }
        .amount-box { font-size: 22px; font-weight: bold; }
        .checkbox { display: inline-block; width: 8px; height: 8px; border: 1px solid #000; margin-right: 3px; vertical-align: middle; }
    </style>
</head>
<body>

    {{-- ===================== KOP: LOGO/MINI-LETTERHEAD | JUDUL | NO/TGL ===================== --}}
    <table>
        <tr>
            <td style="width: 33%; vertical-align: middle;">
                <div class="box">
                    <img src="{{ public_path('images/logo-spr-short.png') }}" style="width: 100%;" alt="KJPP Sugianto Prasodjo dan Rekan">
                </div>
            </td>
            <td style="width: 34%; vertical-align: middle;">
                <div class="doc-title">KWITANSI</div>
                <div class="doc-sub">RECEIPT</div>
            </td>
            <td style="width: 33%; vertical-align: middle;">
                <table class="small">
                    <tr>
                        <td>No.<br>Number</td>
                        <td>:</td>
                        <td class="bold">{{ $invoice->kwitansi_number }}</td>
                    </tr>
                    <tr>
                        <td>Tgl.<br>Date</td>
                        <td>:</td>
                        <td class="bold">{{ optional($invoice->payment_date)->translatedFormat('d F Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===================== ISI KWITANSI ===================== --}}
    <table class="frame">
        <tr class="dots-row">
            <td class="label-col">
                <span class="underline">Sudah terima dari</span><br>
                <span class="italic small">Received Form</span>
            </td>
            <td>{{ $project->instructingClient->client_name }}</td>
        </tr>
        <tr class="dots-row">
            <td class="label-col">
                <span class="underline">Banyaknya Uang</span><br>
                <span class="italic small">Amount Received</span>
            </td>
            <td class="bold italic"># {{ \App\Helpers\Terbilang::make($invoice->amount) }} #</td>
        </tr>
        <tr class="dots-row">
            <td class="label-col" rowspan="3">
                <span class="underline">Untuk Pembayaran</span><br>
                <span class="italic small">For payment of</span>
            </td>
            <td>
                Pembayaran {{ $invoice->term_description ?: 'Biaya Jasa Penilaian' }}
                Biaya Jasa Penilaian Properti an. {{ $project->instructingClient->client_name }}
            </td>
        </tr>
        <tr class="dots-row">
            <td>Sesuai dengan Surat Penawaran No. {{ $project->proposal_number }}</td>
        </tr>
        <tr class="dots-row">
            <td>Tanggal {{ $project->effective_proposal_date->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td></td>
            <td>
                <table class="breakdown" style="width: 260px;">
                    <tr><td>Fee</td><td>:</td><td class="text-right">Rp {{ number_format($invoice->net_amount, 0, ',', '.') }}</td></tr>
                    <tr><td>PPN {{ rtrim(rtrim(number_format($invoice->ppn_rate * 100, 2, ',', ''), '0'), ',') }}%</td><td>:</td><td class="text-right">Rp {{ number_format($invoice->ppn_amount, 0, ',', '.') }}</td></tr>
                    <tr class="bold"><td>Total</td><td>:</td><td class="text-right">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===================== NOMINAL BESAR + CARA BAYAR + REKENING ===================== --}}
    <table class="frame">
        <tr>
            <td style="width: 45%;">
                <span class="small">Rp.</span>
                <span class="amount-box">{{ number_format($invoice->amount, 0, ',', '.') }},00</span>
                <br><br>
                <table class="small">
                    <tr>
                        <td style="width: 60px;"><span class="checkbox"></span> CASH</td>
                        <td style="width: 100px;"><span class="checkbox"></span> CHEQUE / BG</td>
                        <td><span class="checkbox"></span> TRANSFER</td>
                    </tr>
                </table>
                <br>
                <table class="small">
                    <tr><td style="width: 60px; vertical-align: top;">Bank<br><span class="italic">Bank</span></td><td class="dots">&nbsp;</td></tr>
                    <tr><td style="vertical-align: top; padding-top: 8px;">Nomer<br><span class="italic">Number</span></td><td class="dots">&nbsp;</td></tr>
                    <tr><td style="vertical-align: top; padding-top: 8px;">Tanggal<br><span class="italic">Date</span></td><td class="dots">&nbsp;</td></tr>
                </table>
            </td>
            <td style="width: 55%; vertical-align: top;">
                Mohon ditransfer ke Rekening Kami:<br><br>
                <table class="small">
                    <tr><td style="width: 120px;">Pemilik Rekening</td><td>: {{ $bank_info['account_name'] ?? config('kjpp.company_name') }}</td></tr>
                    <tr><td>Bank</td><td>: {{ $bank_info['bank_name'] ?? '-' }}</td></tr>
                    @if (!empty($bank_info['branch']))
                        <tr><td>Cabang</td><td>: {{ $bank_info['branch'] }}</td></tr>
                    @endif
                    <tr><td>Account</td><td>: {{ $bank_info['account_number'] ?? '-' }}</td></tr>
                </table>
                <br><br><br>
                <div class="text-right">
                    <strong>{{ config('kjpp.signatory.name') }}, MAPPI (Cert.)</strong><br>
                    <span class="italic">{{ config('kjpp.signatory.title') }}</span>
                </div>
            </td>
        </tr>
    </table>

    <p class="small text-center" style="margin-top: 10px;">
        Kwitansi ini baru dianggap sah, setelah pembayaran dengan Bilyet Giro/Cek tsb, dapat diuangkan<br>
        <span class="italic">This receipt will be cleared after Bilyet Giro/Cheque can be cleared</span>
    </p>

    {{-- ===================== FOOTER ===================== --}}
    <div style="position: fixed; bottom: -55px; left: 0; right: 0; text-align: center; font-size: 8px; color: #333;">
        @foreach (config('kjpp.footer.lines') as $line)
            {{ $line }}<br>
        @endforeach
    </div>

</body>
</html>
