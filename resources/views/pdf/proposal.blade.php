<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 100px 60px 90px 60px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; }
        h1 { font-size: 14px; text-align: center; text-decoration: underline; margin-bottom: 4px; }
        h2 { font-size: 12px; margin-top: 18px; margin-bottom: 6px; border-bottom: 1px solid #333; padding-bottom: 3px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .mt-10 { margin-top: 10px; }
        .mt-20 { margin-top: 20px; }
        .small { font-size: 9.5px; color: #333; }
        .box { border: 1px solid #999; padding: 8px; }
        .label-col { width: 190px; vertical-align: top; padding: 3px 0; }
        .val-col { vertical-align: top; padding: 3px 0; }
    </style>
</head>
<body>

    {{-- ===================== KOP SURAT (ruang untuk logo/letterhead cetak) ===================== --}}
    <table style="margin-bottom: 15px;">
        <tr>
            <td style="width: 90px;">
                {{-- Ganti src dengan logo resmi, mis: public_path('images/logo-kjpp.png') --}}
                <img src="{{ config('kjpp.company_logo') }}" style="width: 80px;" alt="Logo">
            </td>
            <td class="text-center">
                <div style="font-size: 15px; font-weight: bold;">{{ config('kjpp.company_name') }}</div>
                <div class="small">{{ config('kjpp.company_address') }}</div>
                <div class="small">Telp: {{ config('kjpp.company_phone') }} | Email: {{ config('kjpp.company_email') }}</div>
                <div class="small">Izin Usaha KJPP No. 2.15.0131 — Sugianto Prasodjo dan Rekan</div>
            </td>
            <td style="width: 90px;"></td>
        </tr>
    </table>
    <hr style="border-top: 2px solid #1a1a1a;">

    <h1 class="mt-20">SURAT PENAWARAN JASA PENILAIAN</h1>
    <p class="text-center small">No. {{ $project->proposal_number }}</p>

    <p class="mt-10">
        {{ $generated_at }}<br><br>
        Kepada Yth,<br>
        <strong>{{ $project->instructingClient->client_name }}</strong><br>
        {{ $project->instructingClient->address }}
    </p>

    <p>Dengan hormat,</p>
    <p>
        Sehubungan dengan permohonan jasa penilaian atas objek di bawah ini, bersama surat ini kami sampaikan
        penawaran jasa penilaian sebagai berikut:
    </p>

    {{-- ===================== DATA PROYEK ===================== --}}
    <table class="mt-10">
        <tr>
            <td class="label-col">Tujuan Penilaian</td>
            <td class="val-col">
                @switch ($project->proposal_purpose)
                    @case ('Jual Beli')
                        : Penilaian untuk tujuan <strong>Jual Beli</strong>
                        untuk kepentingan {{ $project->instructingClient->client_name }}.
                        @break
                    @case ('Penjaminan Utang')
                        : Penilaian untuk tujuan <strong>Penjaminan Utang</strong>
                        pada {{ $project->instructingClient->client_name }}.
                        @break
                    @case ('Lelang')
                        : Penilaian untuk tujuan <strong>Lelang</strong>
                        pada {{ $project->instructingClient->client_name }}.
                        @break
                    @case ('Pelaporan Keuangan')
                        : Penilaian ini dilakukan untuk tujuan <strong>Pelaporan Keuangan</strong>.
                        Aset berupa {{ $project->asset_type }} diklasifikasikan sebagai
                        <strong>{{ $project->psak_classification }}</strong>, dengan pengukuran nilai wajar
                        mengacu pada PSAK 113.
                        @break
                @endswitch
            </td>
        </tr>
        <tr>
            <td class="label-col">Pemilik Aset</td>
            <td class="val-col">: {{ $project->property_owner_name }}</td>
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
            <td class="label-col">Tanggal Penilaian</td>
            <td class="val-col">
                : {{ $project->valuation_date?->translatedFormat('d F Y') ?? '(menunggu jadwal survei/cut-off)' }}
                @if ($project->proposal_purpose === 'Pelaporan Keuangan')
                    <span class="small">(tanggal cut-off laporan keuangan)</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label-col">Pengguna Laporan</td>
            <td class="val-col">
                :
                @foreach ($project->intendedUsers as $user)
                    {{ $user->client_name }}{{ !$loop->last ? ', ' : '' }}
                @endforeach
            </td>
        </tr>
        <tr>
            <td class="label-col">Biaya Jasa Penilaian</td>
            <td class="val-col">: Rp {{ number_format($project->service_fee, 0, ',', '.') }},- (belum termasuk PPN)</td>
        </tr>
    </table>

    {{-- ===================== JANGKA WAKTU INVESTIGASI (SLA DINAMIS) ===================== --}}
    <h2>Jangka Waktu Penyelesaian Pekerjaan</h2>
    @if ($project->report_style === 'Terinci')
        <p>
            Laporan penilaian akan disusun dalam bentuk <strong>Laporan Terinci (Comprehensive Style Report)</strong>,
            dengan estimasi jangka waktu penyelesaian pekerjaan selama <strong>7 (tujuh) hari kerja</strong>
            terhitung sejak tanggal survei lapangan dilaksanakan dan seluruh data pendukung diterima secara lengkap.
        </p>
    @else
        <p>
            Laporan penilaian akan disusun dalam bentuk <strong>Laporan Ringkas (Short Form Report)</strong>,
            dengan estimasi jangka waktu penyelesaian pekerjaan selama <strong>3 (tiga) hari kerja</strong>
            terhitung sejak tanggal survei lapangan dilaksanakan dan seluruh data pendukung diterima secara lengkap.
        </p>
    @endif

    {{-- ===================== DASAR NILAI (KONDISIONAL PER JENIS PROPOSAL) ===================== --}}
    <h2>Dasar Nilai</h2>
    <p>
        Sesuai dengan tujuan penilaian, Dasar Nilai yang digunakan adalah <strong>{{ $project->value_basis_label }}</strong>.
    </p>

    @if ($project->primary_value_basis === 'Nilai Pasar')
        <p>
            <strong>Nilai Pasar (Market Value)</strong> sebagaimana didefinisikan dalam Standar Penilaian Indonesia
            (SPI) 101 adalah estimasi sejumlah uang yang dapat diperoleh atau dibayar untuk penukaran suatu aset
            pada tanggal penilaian, antara pembeli yang berminat membeli dengan penjual yang berminat menjual,
            dalam suatu transaksi bebas ikatan, yang pemasarannya dilakukan secara layak, dimana kedua pihak
            masing-masing bertindak atas dasar pemahaman yang dimilikinya, kehati-hatian, dan tanpa paksaan.
        </p>
    @else
        <p>
            <strong>Nilai Wajar (Fair Value)</strong> adalah harga yang akan diterima dari penjualan aset atau
            dibayarkan untuk pengalihan liabilitas dalam transaksi yang teratur diantara pelaku pasar pada
            tanggal pengukuran, sebagaimana diatur dalam SPI 102.3.17 — Dasar Nilai Selain Nilai Pasar
            @if ($project->shows_pojk28_clause)
                , serta mengacu pada ketentuan POJK 28/POJK.04/2021 mengingat klien merupakan Perusahaan Terbuka
            @endif
            .
        </p>
    @endif

    @if ($project->requires_liquidation_value)
        <p>
            @if ($project->liquidation_value_is_mandatory)
                Sejalan dengan tujuan Lelang, laporan penilaian ini juga akan menyajikan
                <strong>Indikasi Nilai Likuidasi (Liquidation Value)</strong>
            @else
                Pada beberapa kasus, bank dapat meminta Penilai untuk memberikan opini
                <strong>Indikasi Nilai Likuidasi</strong>
            @endif
            sesuai SPI 102.3.5, yaitu estimasi sejumlah uang yang dapat diperoleh dari penjualan aset dalam
            jangka waktu yang lebih singkat dari kelaziman pemasaran suatu aset. Indikasi nilai ini bersifat
            estimasi awal yang tidak mengikat, umumnya diperoleh dengan mengenakan diskon berkisar
            <strong>20% (dua puluh persen) sampai dengan 40% (empat puluh persen)</strong> dari Nilai Pasar.
        </p>
    @endif

    @if ($project->requires_exposure_time)
        <h2>Waktu Ekspos (Exposure Time)</h2>
        <p>
            Waktu Ekspos adalah estimasi waktu dari suatu aset yang dinilai, dianggap telah ditawarkan dalam
            suatu pasar hipotesis untuk dijual sesuai definisi Nilai Pasar pada tanggal penilaian. Estimasi
            waktu ini bersifat retrospektif, didasarkan pada analisis kejadian masa lalu dengan asumsi adanya
            transaksi dalam pasar terbuka dan kompetitif, sesuai Pedoman Penilaian Indonesia – 05.
        </p>
    @endif

    {{-- ===================== LEGALITAS & PENANGGUNG JAWAB ===================== --}}
    <h2>Penilai Publik Penanggung Jawab</h2>
    <p class="small">
        Pekerjaan penilaian ini akan dilaksanakan di bawah tanggung jawab Penilai Publik
        <strong>Arief Rachman Setiady, S.M., M.M., MAPPI (Cert.)</strong>, pemegang Izin Penilai Publik
        No. <strong>P-1.25.00690</strong>, yang bernaung di bawah <strong>{{ config('kjpp.company_name') }}</strong>
        (Sugianto Prasodjo dan Rekan) dengan Izin Usaha KJPP No. <strong>2.15.0131</strong>, sesuai ketentuan
        peraturan perundang-undangan yang berlaku di bidang jasa penilai publik di Indonesia.
    </p>

    <h2>Ketentuan Umum</h2>
    <p class="small">{{ config('kjpp.standard_clauses.independence') }}</p>
    <p class="small">{{ config('kjpp.standard_clauses.validity') }}</p>
    <p class="small">{{ config('kjpp.standard_clauses.confidential') }}</p>

    {{-- ===================== TANDA TANGAN + QR CODE ===================== --}}
    <table class="mt-20" style="margin-top: 40px;">
        <tr>
            <td style="width: 60%;">
                <div class="small">Hormat kami,</div>
                <div class="small">{{ config('kjpp.company_name') }}</div>
                <div style="height: 70px;"></div>
                <div style="border-top: 1px solid #333; width: 220px;"></div>
                <div class="small"><strong>Arief Rachman Setiady, S.M., M.M., MAPPI (Cert.)</strong></div>
                <div class="small">Penilai Publik — Izin No. P-1.25.00690</div>
            </td>
            <td style="width: 40%; vertical-align: bottom;">
                {{-- Placeholder kotak QR Code keabsahan dokumen --}}
                <table>
                    <tr>
                        <td style="width: 80px; height: 80px; border: 1px solid #999;" class="text-center small">
                            QR CODE<br>VERIFIKASI
                        </td>
                        <td class="small" style="padding-left: 8px;">
                            Pindai kode QR di samping untuk memverifikasi keabsahan dokumen ini melalui sistem
                            kami.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($project->shows_bank_acknowledgement)
        {{-- ===================== TANDA TANGAN TAMBAHAN: BANK (KHUSUS LELANG) ===================== --}}
        <table style="margin-top: 30px;">
            <tr>
                <td>
                    <div class="small">Mengetahui,</div>
                    <div class="small"><strong>{{ $project->instructingClient->client_name }}</strong></div>
                    <div style="height: 70px;"></div>
                    <div style="border-top: 1px solid #333; width: 220px;"></div>
                    <div class="small">Jabatan: ______________________</div>
                </td>
            </tr>
        </table>
    @endif

</body>
</html>
