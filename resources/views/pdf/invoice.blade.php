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

        {{-- ===== Kop surat ===== --}}
        .lh-logo { text-align: center; margin-bottom: 6px; }
        .lh-logo img { width: 380px; }
        hr.thick { border: none; border-top: 2.5px solid #000; margin: 4px 0 16px; }

        .doc-title { text-align: center; font-size: 19px; font-weight: bold; margin: 4px 0 16px; }

        {{-- ===== Kotak invoice ===== --}}
        .frame { border: 1.3px solid #000; }
        .frame td { padding: 7px 10px; vertical-align: top; }
        .b-bottom { border-bottom: 1.3px solid #000; }
        .b-top { border-top: 1.3px solid #000; }
        .no-box { width: 230px; }
    </style>
</head>
<body>

    {{-- ===================== KOP SURAT ===================== --}}
    <div class="lh-logo">
        <img src="{{ public_path('images/logo-spr-long.png') }}" alt="KJPP Sugianto Prasodjo dan Rekan">
    </div>
    <hr class="thick">

    <div class="doc-title">I&nbsp;N&nbsp;V&nbsp;O&nbsp;I&nbsp;C&nbsp;E</div>

    {{-- ===================== KOTAK INVOICE ===================== --}}
    <table class="frame">
        <tr>
            <td class="b-bottom" style="width: 60%;">
                Telah diterima Dari
            </td>
            <td class="b-bottom text-right no-box">
                No. <strong>{{ $invoice->invoice_number }}</strong>
            </td>
        </tr>
        <tr>
            <td class="b-bottom" colspan="2">
                <strong>{{ $project->instructingClient->client_name }}</strong><br>
                <span class="small">{{ $project->instructingClient->address }}</span>
            </td>
        </tr>

        <tr>
            <td class="b-bottom bold" style="width: 60%;">URAIAN / DESCRIPTION</td>
            <td class="b-bottom bold text-right no-box">JUMLAH / AMOUNT</td>
        </tr>

        <tr>
            <td style="width: 60%;">
                <span class="underline bold">UNTUK PEMBAYARAN</span><br>
                <span class="italic small">FOR PAYMENT</span>
                <br><br>
                <span class="bold italic">
                    Pembayaran {{ $invoice->term_description ?: 'Biaya Jasa Penilaian' }}
                    Biaya Jasa Penilaian Properti an. {{ $project->instructingClient->client_name }}
                    yang berlokasi di :
                </span>
                <br>
                @foreach ($project->valuationObjects as $object)
                    <br>{{ $loop->iteration }}. {{ $object->location }}<br>
                @endforeach
                <br>
                Sesuai dengan Surat Penawaran No. {{ $project->proposal_number }}<br>
                Tanggal {{ $project->effective_proposal_date->translatedFormat('d F Y') }}
                <br><br>
                Ppn {{ rtrim(rtrim(number_format($invoice->ppn_rate * 100, 2, ',', ''), '0'), ',') }}%
            </td>
            <td class="text-right no-box">
                Rp&nbsp;&nbsp;&nbsp;{{ number_format($invoice->net_amount, 0, ',', '.') }}
                <br><br><br><br><br><br><br><br><br><br>
                Rp&nbsp;&nbsp;&nbsp;{{ number_format($invoice->ppn_amount, 0, ',', '.') }}
            </td>
        </tr>

        <tr>
            <td class="b-top" style="width: 60%;">
                <span class="underline bold">TERBILANG</span><br>
                <span class="italic small">THE AMOUNT OF</span>
                <br><br>
                <span class="bold italic"># {{ \App\Helpers\Terbilang::make($invoice->amount) }} #</span>
            </td>
            <td class="b-top">&nbsp;</td>
        </tr>

        <tr>
            <td class="b-top text-center bold" style="width: 60%;">TOTAL</td>
            <td class="b-top text-right bold no-box">Rp&nbsp;&nbsp;&nbsp;{{ number_format($invoice->amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    {{-- ===================== REKENING & TANDA TANGAN ===================== --}}
    <table style="margin-top: 14px;">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                Pembayaran mohon ditransfer ke Rekening:<br>
                <strong>{{ $bank_info['bank_name'] ?? '-' }}</strong><br>
                @if (!empty($bank_info['branch']))
                    <strong>Cabang {{ $bank_info['branch'] }}</strong><br>
                @endif
                <strong>A/C. {{ $bank_info['account_number'] ?? '-' }}</strong><br>
                <strong>{{ $bank_info['account_name'] ?? config('kjpp.company_name') }}</strong>
            </td>
            <td style="width: 45%; vertical-align: top; text-align: center;">
                Jakarta, {{ $invoice->displayDate->translatedFormat('d F Y') }}
                <br><br><br><br><br>
                {{-- Penandatangan = Penanggung Jawab proyek (akun user), data
                     baku config hanya cadangan (2026-09-15, feedback user). --}}
                <strong>{{ optional($project->signedBy)->name ?: config('kjpp.signatory.name') }}, MAPPI (Cert.)</strong><br>
                {{ optional($project->signedBy)->partner_status ?: config('kjpp.signatory.title') }}
            </td>
        </tr>
    </table>

    {{-- ===================== FOOTER ===================== --}}
    <div style="position: fixed; bottom: -55px; left: 0; right: 0; text-align: center; font-size: 8px; color: #333;">
        @foreach (config('kjpp.footer.lines') as $line)
            {{ $line }}<br>
        @endforeach
    </div>

</body>
</html>
