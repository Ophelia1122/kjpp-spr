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
                        {{-- Skema Bayar Nanti boleh cetak sebelum dibayar -> pakai tanggal invoice. --}}
                        <td class="bold">{{ ($invoice->payment_date ?? $invoice->display_date)->translatedFormat('d F Y') }}</td>
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
            {{-- Pihak "Telah diterima dari" yang dipilih per invoice (2026-09-14). --}}
            <td>{{ optional($invoice->payer)->client_name }}</td>
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
                Biaya Jasa Penilaian Properti an. {{ $invoice->on_behalf_name }}
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
                {{-- Nominal meniru kwitansi baku kantor: "Rp." besar + angka di atas
                     bidang arsiran bergaris tebal (2026-09-14, feedback user). --}}
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 46px; padding: 0 4px 0 0; font-size: 22px; font-weight: bold; vertical-align: bottom;">Rp.</td>
                        <td style="padding: 3px 6px; font-size: 19px; font-weight: bold; text-align: center; border-top: 3px solid #000; border-bottom: 1.5px solid #000; background-image: url('{{ public_path('images/hatch.png') }}');">
                            {{ number_format($invoice->amount, 0, ',', '.') }},00
                        </td>
                    </tr>
                </table>
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
                {{-- Ruang tanda tangan lebar; nama & jabatan rata tengah di antara
                     awal "Mohon ditransfer" dan bingkai kanan (2026-09-14, feedback user). --}}
                <div class="text-center" style="margin-top: 104px;">
                    {{-- Penandatangan = Penanggung Jawab proyek (akun user), data
                         baku config hanya cadangan (2026-09-15, feedback user). --}}
                    <strong>{{ optional($project->signedBy)->name ?: config('kjpp.signatory.name') }}, MAPPI (Cert.)</strong><br>
                    <span class="italic">{{ optional($project->signedBy)->partner_status ?: config('kjpp.signatory.title') }}</span>
                </div>
            </td>
        </tr>
        {{-- Catatan keabsahan di DALAM tabel, rata tengah, 2 baris dimulai
             bahasa Inggris (2026-09-14, feedback user). --}}
        <tr>
            <td colspan="2" class="small text-center" style="border-top: 1px dotted #999; padding-top: 3px; padding-bottom: 3px;">
                <span class="italic">This receipt will be cleared after Bilyet Giro/Cheque can be cleared</span><br>
                Kwitansi ini baru dianggap sah, setelah pembayaran dengan Bilyet Giro/Cek tsb, dapat diuangkan
            </td>
        </tr>
    </table>

    {{-- Footer alamat kantor dibuang (2026-09-14, feedback user). --}}
</div>
