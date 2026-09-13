{{-- Satu lembar kwitansi. Dicetak DUA kali dalam satu halaman A4 oleh
     pdf/kwitansi.blade.php, dipisah garis putus-putus untuk digunting
     (2026-09-15, feedback user). Semua ukuran di sini sudah dipadatkan
     supaya satu lembar muat di setengah halaman. --}}
<div class="copy">

    {{-- ===================== KOP: LOGO | JUDUL | NO/TGL ===================== --}}
    <table>
        <tr>
            <td style="width: 30%; vertical-align: middle;">
                <div class="box">
                    <img src="{{ public_path('images/logo-spr-short.png') }}" style="width: 100%;" alt="KJPP Sugianto Prasodjo dan Rekan">
                </div>
            </td>
            <td style="width: 38%; vertical-align: middle;">
                <div class="doc-title">KWITANSI</div>
                <div class="doc-sub">RECEIPT</div>
            </td>
            <td style="width: 32%; vertical-align: middle;">
                <table class="small">
                    <tr>
                        <td style="width: 38px;">No.<br>Number</td>
                        <td style="width: 6px;">:</td>
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
            <td class="label-col" rowspan="2">
                <span class="underline">Untuk Pembayaran</span><br>
                <span class="italic small">For payment of</span>
            </td>
            <td>
                Pembayaran {{ $invoice->term_description ?: 'Biaya Jasa Penilaian' }}
                Biaya Jasa Penilaian Properti an. {{ $project->instructingClient->client_name }}
            </td>
        </tr>
        <tr class="dots-row">
            <td>
                Sesuai dengan Surat Penawaran No. {{ $project->proposal_number }}
                tanggal {{ $project->effective_proposal_date->translatedFormat('d F Y') }}
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <table class="breakdown" style="width: 230px;">
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
                <table class="small" style="margin-top: 5px;">
                    <tr>
                        <td style="width: 55px;"><span class="checkbox"></span> CASH</td>
                        <td style="width: 90px;"><span class="checkbox"></span> CHEQUE / BG</td>
                        <td><span class="checkbox"></span> TRANSFER</td>
                    </tr>
                </table>
                <table class="small" style="margin-top: 3px;">
                    <tr><td style="width: 82px; white-space: nowrap;">Bank <span class="italic">/ Bank</span></td><td class="dots">&nbsp;</td></tr>
                    <tr><td>Nomer <span class="italic">/ Number</span></td><td class="dots">&nbsp;</td></tr>
                    <tr><td>Tanggal <span class="italic">/ Date</span></td><td class="dots">&nbsp;</td></tr>
                </table>
            </td>
            <td style="width: 55%; vertical-align: top;">
                Mohon ditransfer ke Rekening Kami:
                <table class="small" style="margin-top: 3px;">
                    <tr><td style="width: 95px;">Pemilik Rekening</td><td>: {{ $bank_info['account_name'] ?? config('kjpp.company_name') }}</td></tr>
                    <tr><td>Bank</td><td>: {{ $bank_info['bank_name'] ?? '-' }}</td></tr>
                    @if (!empty($bank_info['branch']))
                        <tr><td>Cabang</td><td>: {{ $bank_info['branch'] }}</td></tr>
                    @endif
                    <tr><td>Account</td><td>: {{ $bank_info['account_number'] ?? '-' }}</td></tr>
                </table>
                <div class="text-right" style="margin-top: 26px;">
                    {{-- Penandatangan = Penanggung Jawab proyek (akun user), data
                         baku config hanya cadangan (2026-09-15, feedback user). --}}
                    <strong>{{ optional($project->signedBy)->name ?: config('kjpp.signatory.name') }}, MAPPI (Cert.)</strong><br>
                    <span class="italic">{{ optional($project->signedBy)->partner_status ?: config('kjpp.signatory.title') }}</span>
                </div>
            </td>
        </tr>
    </table>

    <p class="small text-center" style="margin: 4px 0 0;">
        Kwitansi ini baru dianggap sah, setelah pembayaran dengan Bilyet Giro/Cek tsb, dapat diuangkan &middot;
        <span class="italic">This receipt will be cleared after Bilyet Giro/Cheque can be cleared</span>
    </p>

    {{-- Footer alamat kantor — ikut di dalam tiap lembar (bukan position:fixed
         per halaman) supaya kedua potongan tetap punya kop alamat sendiri. --}}
    <div class="footer">
        @foreach (config('kjpp.footer.lines') as $line)
            {{ $line }}<br>
        @endforeach
    </div>
</div>
