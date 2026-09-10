{{--
    ============================================================================
    ⚠️ TIDAK DIPAKAI LAGI (per Feature 7).
    Proposal sekarang di-generate sebagai .docx oleh
    App\Services\ProposalDocxBuilder (teks baku: config/proposal_clauses.php),
    lalu di-convert ke PDF via LibreOffice (App\Services\DocxToPdf).
    File ini disimpan sebagai referensi teks/format saja.
    ============================================================================

    SURAT PENAWARAN JASA PENILAIAN — mengikuti FORMAT BAKU master proposal
    KJPP Sugianto Prasodjo dan Rekan (dokumen: 00x_MASTER PROPOSAL_*.docx).

    Teks baku ketentuan perusahaan ditulis apa adanya (tidak diringkas /
    tidak diubah isinya). Bagian yang berubah per-proyek diisi dari data
    sistem; bagian yang belum ada datanya di sistem dibiarkan sebagai
    titik-titik isian manual — persis seperti master.

    Bagian kondisional per jenis proposal (Jual Beli / Penjaminan Utang /
    Lelang / Pelaporan Keuangan) memakai accessor di App\Models\Project.
    ============================================================================
--}}
@php
    use App\Models\Project;
    use App\Helpers\Terbilang;

    $cfg   = config('kjpp');
    $sig   = $cfg['signatory'];
    $bank  = $bank_info ?? $cfg['bank_account'];
    $foot  = $cfg['footer'];

    $pt        = $project->instructingClient;
    $purpose   = $project->proposal_purpose;
    $fee       = (float) $project->service_fee;
    $termin    = round($fee / 2);
    $isLong    = $project->report_style === Project::REPORT_LONG;
    $styleText = $isLong
        ? 'Laporan Penilaian Terinci (Comprehensive Style)'
        : 'Laporan Penilaian Ringkas (Short Form Report)';
    $draft     = $project->sla_draft_days;
    $final     = $project->sla_final_days;
    $totalDays = ($draft && $final) ? ($draft + $final) : null;

    // isian manual singkat / panjang (meniru "----" & "................" pada master)
    $b  = '..................';
    $bb = '................................................';

    $daysText = fn ($n) => $n ? $n . ' (' . Terbilang::words($n) . ')' : '-- (----)';
    $rp       = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.') . ',00';
    $terbilangRp = fn ($n) => '(' . Terbilang::make($n) . ')';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @php $pdfFont = $cfg['pdf_font'] ?? 'Helvetica'; @endphp

        {{-- Arial Narrow (di-bundel di public/fonts). Dipakai bila
             config('kjpp.pdf_font') = 'Arial Narrow'. --}}
        @font-face { font-family: 'Arial Narrow'; font-weight: normal; font-style: normal; src: url("{{ public_path('fonts/arialn.ttf') }}") format("truetype"); }
        @font-face { font-family: 'Arial Narrow'; font-weight: bold;   font-style: normal; src: url("{{ public_path('fonts/arialnb.ttf') }}") format("truetype"); }
        @font-face { font-family: 'Arial Narrow'; font-weight: normal; font-style: italic; src: url("{{ public_path('fonts/arialni.ttf') }}") format("truetype"); }
        @font-face { font-family: 'Arial Narrow'; font-weight: bold;   font-style: italic; src: url("{{ public_path('fonts/arialnbi.ttf') }}") format("truetype"); }

        @page { margin: 116px 64px 120px 64px; }

        body {
            font-family: '{{ $pdfFont }}', 'Helvetica', Arial, sans-serif;
            font-size: {{ $pdfFont === 'Arial Narrow' ? '11.2px' : '10.3px' }};
            color: #000;
            line-height: 1.5;
            text-align: justify;
            counter-reset: sec;
        }

        p { margin: 0 0 7px; }

        /* Kop & footer yang berulang di tiap halaman */
        .pagehead {
            position: fixed; top: -92px; left: 0; right: 0;
            height: 84px;
        }
        .pagehead .kop-name { font-size: 13px; font-weight: bold; letter-spacing: .3px; }
        .pagehead .kop-tag  { font-size: 9px; }
        .pagehead .rule     { border-bottom: 2px solid #000; margin-top: 4px; }

        .pagefoot {
            position: fixed; bottom: -104px; left: 0; right: 0;
            font-size: 7.3px; color: #222; line-height: 1.35;
            border-top: 1px solid #000; padding-top: 3px;
        }
        .pagefoot .pg { text-align: right; font-size: 7.3px; margin-top: 2px; }
        .pageno:after { content: counter(page); }

        h1.title {
            font-size: 13px; text-align: center; font-weight: bold;
            text-decoration: underline; margin: 0 0 2px;
        }

        /* Heading bagian — bernomor, bold, tanpa garis (sesuai master) */
        .h {
            font-weight: bold; font-size: 10.8px;
            margin: 13px 0 4px;
            counter-increment: sec;
        }
        .h:before { content: counter(sec) ". "; }
        .sub { font-weight: bold; margin: 8px 0 2px; }

        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }

        .kv td { padding: 1px 0; vertical-align: top; }
        .kv td.k { width: 165px; }
        .kv td.s { width: 12px; }

        table.obj { margin: 6px 0 4px; border: 1px solid #000; }
        table.obj th, table.obj td {
            border: 1px solid #000; padding: 5px 6px; font-size: 9.6px;
        }
        table.obj th { background: #e9e9e9; text-align: left; font-weight: bold; }
        table.obj td.no { text-align: center; width: 26px; }
        table.obj tr { page-break-inside: avoid; }

        ol.n, ul.d { margin: 2px 0 8px; padding-left: 22px; }
        ol.n li, ul.d li { margin-bottom: 3px; }

        .fill { display: inline-block; border-bottom: 1px dotted #444; min-width: 70px; }
        .center { text-align: center; }
        .mt { margin-top: 10px; }

        .sign td { width: 50%; vertical-align: top; padding-top: 4px; }
        .sign .gap { height: 62px; }
        .sign .line { border-top: 1px solid #000; width: 235px; }
    </style>
</head>
<body>

{{-- ===================== KOP (berulang tiap halaman) ===================== --}}
<div class="pagehead">
    <table>
        <tr>
            <td style="width: 92px;">
                @if (is_file($cfg['company_logo']))
                    <img src="{{ $cfg['company_logo'] }}" style="width: 78px;" alt="Logo">
                @endif
            </td>
            <td class="center">
                <div class="kop-name">{{ $cfg['company_name'] }}</div>
                <div class="kop-tag">{{ $cfg['company_tagline'] }}</div>
                <div class="kop-tag">Izin Usaha KJPP No. {{ $cfg['izin_usaha_no'] }}</div>
            </td>
            <td style="width: 92px;"></td>
        </tr>
    </table>
    <div class="rule"></div>
</div>

{{-- ===================== FOOTER (berulang tiap halaman) ===================== --}}
<div class="pagefoot">
    @foreach (($foot['lines'] ?? []) as $line)
        {{ $line }}<br>
    @endforeach
    <div class="pg">Halaman <span class="pageno"></span></div>
</div>

{{-- ===================== NOMOR & TANGGAL ===================== --}}
<table style="margin-bottom: 6px;">
    <tr>
        <td style="width: 62%;">No. {{ $project->proposal_number }}</td>
        <td class="center" style="width: 38%;">Jakarta, {{ $generated_at }}</td>
    </tr>
</table>

<p>
    Kepada Yth,<br>
    <strong>{{ $pt->client_name }}</strong><br>
    @if ($pt->address)
        {!! nl2br(e($pt->address)) !!}
    @else
        {{ $bb }}<br>{{ $bb }}
    @endif
</p>

<p>Hal : Proposal Biaya Jasa Penilaian an. {{ $pt->client_name }}</p>

<p>Dengan hormat,</p>

<p>
    Sesuai dengan informasi permintaan penilaian yang kami terima melalui email permintaan
    penilaian tanggal <span class="fill">{{ $b }}</span> / surat permintaan penilaian No.
    <span class="fill">{{ $b }}</span> tanggal <span class="fill">{{ $b }}</span> / surat order
    penilaian No. <span class="fill">{{ $b }}</span> tanggal <span class="fill">{{ $b }}</span>
    {{ now()->year }}, mengenai permohonan jasa Penilai untuk melakukan penilaian aset milik
    {{ $pt->client_name }}. Bersama ini kami Kantor Jasa Penilai Publik (KJPP) SUGIANTO PRASODJO
    DAN REKAN mengajukan proposal biaya jasa Penilaian Aset dengan Lingkup Penugasan sebagai
    berikut:
</p>

{{-- ===================== PENJELASAN STATUS PENILAI ===================== --}}
<div class="h">Penjelasan Status Penilai</div>
<p>
    Penilai Publik yang bertanda tangan di dalam Laporan Penilaian ini adalah
    <strong>{{ $sig['name'] ?: 'Nama Penilai Publik' }}, MAPPI (Cert.)</strong> merupakan Penilai
    Publik Properti/Properti &amp; Bisnis dengan Izin Penilai Publik No.
    <span class="fill">{{ $sig['izin_pp_no'] ?: '----' }}</span> berdasarkan Surat Keputusan
    Menteri Keuangan Republik Indonesia Nomor
    <span class="fill">{{ $sig['sk_menkeu_no'] ?: '----' }}</span> tanggal
    <span class="fill">{{ $sig['sk_menkeu_date'] ?: '----' }}</span>.
</p>
<p>
    Penilai bertindak atas nama Kantor Jasa Penilai Publik SUGIANTO PRASODJO DAN REKAN memiliki
    Izin Usaha resmi dari Kementerian Keuangan Republik Indonesia No. {{ $cfg['izin_usaha_no'] }}
    berdasarkan Kepmenkeu No. {{ $cfg['kepmenkeu_no'] }} dari Menteri Keuangan Republik Indonesia.
    KJPP Sugianto Prasodjo dan Rekan adalah perusahaan penilai independen yang terdaftar di
    Masyarakat Profesi Penilai Indonesia (MAPPI) dan terdaftar di Otoritas Jasa Keuangan/OJK
    (d/h Bapepam-LK) berdasarkan Surat Tanda Terdaftar Profesi Penunjang Pasar Modal No.
    {{ $cfg['sttd_ojk_no'] }}. (Tambahkan sesuai sertifikasi yang dimiliki penilai publik)
</p>
<p>
    Sebagai Penilai kami dalam posisi untuk memberikan penilaian yang objektif dan tidak memihak.
    Kami sebagai penilai menyatakan bahwa status kami adalah sebagai penilai independen. Kami
    menyatakan bahwa tidak ada keterlibatan material dan benturan kepentingan baik yang aktual
    maupun bersifat potensial terhadap objek penilaian. Sebagai Penilai kami tegaskan kami
    memiliki kompetensi dalam melakukan penilaian atas objek penilaian termasuk seluruh Penilai,
    tenaga ahli dan staf pelaksana yang terlibat dalam proses penilaian yang dimaksud sehingga
    tidak memerlukan bantuan tenaga ahli dari luar.
</p>

{{-- ===================== IDENTIFIKASI PEMBERI TUGAS ===================== --}}
<div class="h">Identifikasi Pemberi Tugas {{ $pt->client_name }}</div>
<p>
    Pemberi Tugas adalah <strong>{{ $pt->client_name }}</strong>@if ($pt->address), berkedudukan di {{ $pt->address }}@endif.
</p>

{{-- ===================== IDENTIFIKASI PENGGUNA LAPORAN ===================== --}}
<div class="h">Identifikasi Pengguna Laporan</div>
<p>Pengguna Laporan adalah:</p>
<ol class="n">
    @foreach ($project->intendedUsers as $user)
        <li>
            <strong>{{ $user->client_name }}</strong>@if ($user->address), berkedudukan di {{ $user->address }}@endif.
        </li>
    @endforeach
</ol>
@if ($purpose === 'Pelaporan Keuangan')
    <p>
        Kantor Akuntan Publik (KAP) / Auditor sebagai pihak yang melakukan audit atas laporan
        keuangan Perusahaan ({{ $pt->client_name }}).
    </p>
@endif

{{-- ===================== IDENTIFIKASI OBYEK PENILAIAN ===================== --}}
<div class="h">Identifikasi Obyek Penilaian dan Kepemilikan</div>
<p>Obyek Penilaian dalam lingkup penugasan ini adalah :</p>
<table class="obj">
    <tr>
        <th class="no">No.</th>
        <th style="width: 168px;">Jenis Aset/Properti</th>
        <th>Lokasi</th>
        <th style="width: 116px;">Bentuk/Jenis Hak Atas Tanah</th>
        <th style="width: 110px;">Atas Nama</th>
    </tr>
    @foreach ($project->valuationObjects as $object)
        <tr>
            <td class="no">{{ $loop->iteration }}</td>
            <td>
                @foreach ($object->description_lines as $line)
                    {{ $line }}@if (!$loop->last)<br>@endif
                @endforeach
            </td>
            <td>{{ $object->location }}</td>
            <td>{{ $object->ownership_form }}</td>
            <td>{{ $object->owner_name }}</td>
        </tr>
    @endforeach
</table>

<p>
    Kami menegaskan bahwa seluruh pimpinan, rekan, dan staf KJPP Sugianto Prasodjo dan Rekan tidak
    bertanggung jawab atas kebenaran data dan informasi yang disampaikan oleh Pemberi Tugas atau
    pihak lain yang menjadi dasar penilaian ini. Segala bentuk informasi yang diberikan terkait
    objek penilaian sepenuhnya menjadi tanggung jawab Pengguna Laporan apabila pengambilan
    keputusan dilakukan tanpa didasari dokumen yang lengkap dan informasi yang jelas. Oleh karena
    itu, Penilai menyarankan agar Pengguna Laporan melakukan verifikasi secara menyeluruh terhadap
    seluruh dokumen dan informasi yang menjadi dasar penilaian sebelum mengambil keputusan
    berdasarkan laporan yang akan diterbitkan.
</p>

{{-- ===================== JENIS MATA UANG ===================== --}}
<div class="h">Jenis Mata Uang yang Digunakan</div>
<p>Jenis Mata Uang yang akan digunakan dalam laporan penilaian adalah Rupiah (Rp).</p>

{{-- ===================== MAKSUD DAN TUJUAN PENILAIAN ===================== --}}
<div class="h">Maksud dan Tujuan Penilaian</div>
<p>
    <strong>Maksud Penilaian:</strong>
    @switch ($purpose)
        @case ('Lelang')
            Memberikan opini atas Nilai Pasar (Market Value) dan Nilai Likuidasi (Liquidation Value)
            terhadap properti yang dinilai pada tanggal penilaian.
            @break
        @case ('Pelaporan Keuangan')
            Memberikan opini atas Nilai Wajar (Fair Value) terhadap properti yang dinilai pada
            tanggal penilaian.
            @break
        @default
            Memberikan opini atas Nilai Pasar (Market Value) terhadap properti yang dinilai pada
            tanggal penilaian.
    @endswitch
</p>
<p>
    <strong>Tujuan Penilaian:</strong>
    @switch ($purpose)
        @case ('Jual Beli')
            Penilaian untuk tujuan Jual Beli untuk kepentingan {{ $pt->client_name }}.
            @break
        @case ('Penjaminan Utang')
            Penilaian untuk tujuan Penjaminan Utang pada {{ $pt->client_name }}.
            @break
        @case ('Lelang')
            Penilaian untuk tujuan Lelang pada {{ $pt->client_name }}.
            @break
        @case ('Pelaporan Keuangan')
            Penilaian ini dilakukan untuk tujuan Pelaporan Keuangan. Aset berupa
            {{ $project->asset_type ?: '…sebutkan objek penilaian…' }} diklasifikasikan sebagai
            {{ $project->psak_classification ?: 'Aset Tetap/Investasi/Persediaan/lainnya' }}
            sesuai dengan PSAK 216/240/202, dengan pengukuran nilai wajar mengacu pada PSAK 113.
            @break
    @endswitch
</p>

{{-- ===================== DASAR NILAI ===================== --}}
<div class="h">Dasar Nilai</div>
<p>Sesuai dengan tujuan penilaian, Dasar Nilai yang digunakan adalah {{ $project->value_basis_label }}.</p>

@if ($project->primary_value_basis === 'Nilai Pasar')
    <div class="sub">NILAI PASAR (Market Value)</div>
    <p>
        Nilai Pasar didefinisikan sebagai estimasi sejumlah uang yang dapat diperoleh atau dibayar
        untuk penukaran suatu aset atau liabilitas pada tanggal penilaian, antara pembeli yang
        berminat membeli dengan penjual yang berminat menjual, dalam suatu transaksi bebas ikatan,
        yang pemasarannya dilakukan secara layak, di mana kedua pihak masing-masing bertindak atas
        dasar pemahaman yang dimilikinya, kehati-hatian dan tanpa paksaan. (SPI 101.3.1 - Nilai
        Pasar sebagai Dasar Nilai).
    </p>
@else
    <div class="sub">NILAI WAJAR (Fair Value)</div>
    <p>
        Nilai Wajar adalah harga yang akan diterima dari penjualan aset atau dibayarkan untuk
        pengalihan liabilitas dalam transaksi yang teratur diantara pelaku pasar pada tanggal
        pengukuran. (SPI 102.3.17 - Dasar Nilai selain Nilai Pasar{{ $project->shows_pojk28_clause ? '; POJK 28/POJK.04/2021' : '' }}).
    </p>
@endif

@if ($purpose === 'Lelang')
    <div class="sub">NILAI LIKUIDASI (Liquidation Value)</div>
    <p>
        Nilai Likuidasi adalah sejumlah uang yang mungkin diterima dari penjualan suatu aset dalam
        jangka waktu yang relatif pendek untuk dapat memenuhi jangka waktu pemasaran dalam definisi
        Nilai Pasar. Pada beberapa situasi, Nilai Likuidasi dapat melibatkan penjual yang tidak
        berminat menjual, dan pembeli yang membeli dengan mengetahui situasi yang tidak
        menguntungkan penjual. (SPI 102.3.5 - Dasar Nilai Selain Nilai Pasar).
    </p>
    <div class="sub">Waktu Ekspos (Exposure Time)</div>
    <p>
        Waktu Ekspos (Exposure Time) adalah estimasi waktu dari suatu aset yang dinilai, dianggap
        telah ditawarkan dalam suatu pasar hipotesis untuk dijual sesuai definisi Nilai Pasar pada
        tanggal penilaian. Estimasi waktu (retrospektif) yang didasarkan suatu analisis kejadian
        masa lalu dengan asumsi adanya transaksi dalam pasar terbuka dan kompetitif. (Pedoman
        Penilaian Indonesia - 05)
    </p>
@elseif ($purpose === 'Penjaminan Utang')
    <p>
        Pada beberapa kasus, bank dapat meminta Penilai untuk memberikan opini Nilai Likuidasi pada
        saat proses pemberian kredit (penilaian untuk penjaminan utang) dan dasar nilai ini
        dinyatakan sebagai Indikasi Nilai Likuidasi. Indikasi nilai ini hanya merupakan estimasi
        awal yang tidak mengikat dan tidak dapat digunakan pada saat terjadi pelepasan kredit macet
        atau pengambilalihan aset jaminan oleh Bank. Pada umumnya Indikasi Nilai Likuidasi diperoleh
        dengan mengenakan diskon sebesar 20% sampai dengan 40% dari Nilai Pasar (Interpretasi SPI
        102 Butir 3.5).
    </p>
@endif

{{-- ===================== TANGGAL PENILAIAN ===================== --}}
<div class="h">Tanggal Penilaian</div>
@if ($purpose === 'Pelaporan Keuangan')
    <p>
        Tanggal penilaian diartikan dalam SPI sebagai tanggal pada saat nilai dinyatakan dan
        diberlakukan. Tanggal ini berbeda dengan tanggal laporan penilaian yang akan diterbitkan
        atau tanggal dimana inspeksi akan dilakukan. Dalam penilaian ini tanggal penilaian
        ditetapkan berdasarkan tanggal pelaporan keuangan yaitu tanggal
        {{ $project->valuation_date?->translatedFormat('d F Y') ?? '…………………' }}. Tanggal ini
        berbeda dari tanggal penyelesaian laporan penilaian maupun tanggal inspeksi lapangan.
    </p>
    <p>
        Sehubungan dengan kemungkinan perubahan yang terjadi terhadap kondisi pasar dan kondisi
        aset/properti yang dinilai, maka laporan penilaian ini hanya dapat merepresentasikan tentang
        opini Nilai Wajar pada saat tanggal penilaian. Kami asumsikan tidak adanya kejadian luar
        biasa (subsequent event) setelah tanggal penilaian yang dapat mempengaruhi hasil penilaian
        secara keseluruhan/bersifat signifikan terhadap aset/properti yang dinilai tersebut.
    </p>
@else
    <p>
        Tanggal penilaian diartikan dalam SPI sebagai tanggal pada saat nilai dinyatakan dan
        diberlakukan. Tanggal ini berbeda dengan tanggal laporan penilaian yang akan diterbitkan
        atau tanggal dimana inspeksi akan dilakukan. Dalam penilaian ini tanggal penilaian
        ditetapkan berdasarkan tanggal terakhir inspeksi lapangan
        @if ($project->valuation_date)
            (tanggal penilaian: {{ $project->valuation_date->translatedFormat('d F Y') }})
        @endif.
        Sehubungan dengan kemungkinan perubahan yang terjadi terhadap kondisi pasar dan kondisi
        aset/properti yang dinilai, maka laporan penilaian ini hanya dapat merepresentasikan tentang
        opini {{ $project->value_basis_label }} pada saat tanggal penilaian. Kami asumsikan tidak
        adanya kejadian luar biasa setelah tanggal penilaian yang dapat mempengaruhi hasil penilaian
        secara keseluruhan/bersifat signifikan terhadap aset/properti yang dinilai tersebut.
    </p>
@endif

{{-- ===================== TINGKAT KEDALAMAN INVESTIGASI ===================== --}}
<div class="h">Tingkat Kedalaman Investigasi</div>
<p>Penilaian ini dilakukan dengan batasan investigasi sebagai berikut:</p>
<ul class="d">
    <li>
        Data dan informasi atas objek penilaian dan kelengkapannya kami peroleh dari pemberi tugas
        dan/atau pemilik aset/properti serta pihak terkait lainnya. Pemberi Tugas dan Pengguna
        Laporan memberikan pembebasan tanggung jawab apapun kepada KJPP Sugianto Prasodjo dan Rekan
        apabila data dan informasi tersebut tidak benar/tidak sesuai dengan fakta sebenarnya.
    </li>
    <li>
        Investigasi dilakukan melalui proses pengumpulan data dengan cara inspeksi, penelaahan,
        perhitungan dan analisis.
    </li>
    <li>
        Inspeksi dilakukan dengan disertai surat tugas atau persetujuan inspeksi serta dilengkapi
        berita acara inspeksi yang ditandatangani oleh Pemberi Tugas dan/atau pemilik objek atau
        pihak yang dikuasakan.
    </li>
    <li>
        Bila ditemukan adanya batasan tingkat kedalaman investigasi, maka inspeksi kami lakukan
        secara sampling. Sedangkan jika aset yang di inspeksi dinformasikan tidak dapat diperiksa,
        maka hal tersebut akan dicatatkan sebagai kondisi pembatas yang berhubungan dengan asumsi
        khusus.
    </li>
    <li>
        Dalam hal terdapat perbedaan objek penilaian atau item lainnya yang dinyatakan dalam Surat
        Perjanjian Kerja (SPK) dengan hasil investigasi, maka perbedaan tersebut akan dinyatakan
        dalam berita acara perubahan Surat Perjanjian Kerja (SPK) yang ditandatangani oleh Penilai,
        Pemberi Tugas dan/atau Pemilik Objek Penilaian.
    </li>
    <li>
        Apabila inspeksi lapangan tidak dapat dilakukan karena keberadaan objek penilaian tidak
        diketahui, maka penugasan dimaksud tidak dapat diteruskan atau batal.
    </li>
    <li>
        Penilaian ini menggunakan luas objek penilaian sebagaimana tercantum dalam sertipikat.
        Penilai hanya melakukan verifikasi terbatas pada bagian tertentu yang dapat diidentifikasi
        secara fisik, tanpa melakukan pengukuran atas keseluruhan tanah dimaksud. Dengan demikian,
        kebenaran luas objek penilaian sepenuhnya menjadi tanggung jawab Pemberi Tugas/Pemilik.
    </li>
    <li>TKI lainnya.</li>
</ul>

{{-- ===================== SIFAT & SUMBER INFORMASI ===================== --}}
<div class="h">Sifat dan Sumber Informasi yang Dapat Diandalkan</div>
<p>
    Informasi dan data yang relevan namun tidak membutuhkan verifikasi, data disetujui untuk
    digunakan sepanjang sumber data tersebut dipublikasikan pada tingkat nasional maupun
    internasional. Sumber data tersebut antara lain pada Badan Pertanahan Nasional, Bank Indonesia,
    Bursa Efek Indonesia dan Negara Lain, Data Pemerintah Kota termasuk Badan Pusat Statistik (BPS),
    Asosiasi Profesi Penilai di Indonesia maupun di luar negeri, sumber lainnya yang dapat
    dipercaya.
</p>

{{-- ===================== ASUMSI UMUM DAN ASUMSI KHUSUS ===================== --}}
<div class="h">Asumsi Umum dan Asumsi Khusus</div>
<p>
    Asumsi umum adalah hal yang wajar untuk di terima sebagai fakta dalam kontek perusahaan
    penilaian tanpa penyelidikan tertentu atau verifikasi, hal tersebut di nyatakan untuk dapat
    diterima dalam pemahaman penilaian. Asumsi dalam penilaian ini adalah sebagai berikut:
</p>
<ul class="d">
    <li>Properti yang ditunjukkan kepada kami adalah benar merupakan properti dalam penilaian.</li>
    <li>
        Objek penilaian diasumsikan didukung dokumen hak kepemilikan/penguasaan yang sah menurut
        peraturan perundang-undangan, dapat dialihkan atau dibebani sesuai ketentuan, serta bebas
        dari ikatan, sengketa, tuntutan, atau klaim pihak ketiga, kecuali apabila secara tegas
        diungkapkan kepada Penilai. Penilai tidak melakukan audit legal; kebenaran dan keabsahan
        dokumen sepenuhnya menjadi tanggung jawab Pemberi Tugas/Pemilik.
    </li>
    <li>
        Nilai yang dicantumkan dalam laporan ini serta setiap nilai lain dalam laporan yang
        merupakan bagian dari properti yang dinilai hanya berlaku sesuai dengan maksud dan tujuan
        penilaian. Nilai yang digunakan dalam laporan penilaian ini tidak boleh digunakan untuk
        tujuan penilaian lain yang dapat mengakibatkan terjadinya kesalahan interprestasi.
    </li>
    <li>
        Objek penilaian yang terdapat di luar identifikasi secara sampling diasumsikan adalah benar,
        mendekati karakteristik yang sama dengan objek yang diperiksa secara sampling.
    </li>
    <li>
        Bagian-bagian bangunan yang tidak terlihat seperti struktur, pondasi dan Tingkat kekerasan
        dinding terpenuhi sebagaimana mestinya dan berfungsi dengan baik.
    </li>
    <li>
        Bagian-bagian mesin yang tidak terlihat seperti struktur, konstruksi, korosi, dan bagian
        lainnya terpenuhi sebagaimana mestinya dan berfungsi dengan baik.
    </li>
    <li>
        Perbedaan kondisi yang mungkin terjadi antara tanggal penilaian dengan waktu penggunaan
        hasil penilaian dapat menurunkan relevansi opini nilai terhadap kebutuhan pengguna hasil
        penilaian, dikarenakan adanya perbedaan akses data dan informasi serta asumsi dan analisis
        penilaian. Apabila pengguna hasil penilaian menemukan kondisi tersebut, disarankan untuk
        menugaskan Penilai melakukan review terhadap penugasan yang telah dilaksanakan dan apabila
        dimungkinkan dan dibutuhkan, Penilai dapat melakukan penilaian ulang dengan mengulang
        kembali prosedur penilaian yang sebelumnya dilakukan, secara lebih lengkap. Proses dan
        prosedur tersebut harus dituangkan dalam penugasan yang berdiri sendiri dan berbeda dengan
        penugasan penilaian sebelumnya.
    </li>
</ul>
<p>
    Asumsi Khusus adalah asumsi yang berbeda dari fakta yang sebenarnya pada tanggal penilaian atau
    hal yang tidak akan diberatkan oleh sebagian kecil pelaku pasar dalam suatu transaksi pada
    tanggal penilaian. Asumsi yang digunakan untuk penilaian aset ini sesuai dengan KEPI &amp; SPI
    Edisi VII - 2018, Edisi Revisi SPI 103 (Lingkup Penugasan) Tahun 2020.
</p>
<p>
    Asumsi Khusus yang diperlukan dalam penilaian ini akan ditetapkan dan dirumuskan setelah Penilai
    melakukan inspeksi lapangan serta memperoleh data pendukung yang relevan. Asumsi Khusus tersebut
    akan diungkapkan secara jelas dalam Laporan Penilaian.
</p>

{{-- ===================== PERSETUJUAN UNTUK PUBLIKASI ===================== --}}
<div class="h">Persyaratan atas Persetujuan untuk Publikasi</div>
<p>
    Hasil Laporan penilaian dan/atau referensi yang melampirinya hanya ditujukan untuk pemberi
    tugas dan pengguna laporan. Kami tidak bertanggung jawab kepada pihak ketiga, dan baik sebagian
    maupun keseluruhan laporan atau rujukan terhadap laporan ini tidak dibenarkan untuk diterbitkan
    dalam dokumen apapun, pernyataan, edaran, ataupun untuk dikomunikasikan kepada pihak ketiga
    tanpa persetujuan tertulis terlebih dahulu dari kami untuk format maupun konteks di mana akan
    dimunculkan.
</p>

{{-- ===================== KONFIRMASI BERDASARKAN SPI ===================== --}}
<div class="h">Konfirmasi bahwa Penilaian dilakukan Berdasarkan SPI</div>
<p>
    Penilaian ini dilakukan berdasarkan Kode Etik (KEPI) &amp; Standar Penilaian Indonesia (SPI)
    Edisi VII Tahun 2018 dan SPI Edisi VII Revisi Tahun 2020 SPI-3xx
    (<span class="fill">{{ $b }}</span> sesuaikan dengan jenis properti) sesuai ketentuan yang
    berlaku dan menjadi bagian yang tidak terpisahkan dari standar penilaian yang digunakan.
</p>

{{-- ===================== LAPORAN PENILAIAN ===================== --}}
<div class="h">Laporan Penilaian</div>
<p>
    Jenis laporan penilaian yang akan disampaikan adalah {{ $styleText }}, disusun dengan
    menggunakan Bahasa Indonesia. Jangka waktu pelaksanaan Investigasi dan penyusunan laporan
    penilaian adalah jangka waktu {{ $daysText($totalDays) }} hari kerja dihitung sejak tanggal
    inspeksi terakhir dan tanggal penerimaan data terakhir sebagaimana tercatat dalam korespondensi
    (email/surat) atau daftar serah terima data dari Pemberi Tugas, dengan waktu pengerjaan sebagai
    berikut:
</p>
<ul class="d">
    <li>
        Laporan Draft / Resume Penilaian dalam waktu {{ $daysText($draft) }} hari kerja setelah
        inspeksi Lapangan dan penerimaan data terakhir dimaksud.
    </li>
    <li>
        Laporan Final akan disampaikan dalam waktu {{ $daysText($final) }} hari kerja setelah
        laporan resume/draft penilaian disetujui.
    </li>
</ul>
<p>Struktur Laporan Penilaian meliputi:</p>
<ul class="d">
    <li>Pendahuluan</li>
    <li>Lingkup Penugasan</li>
    <li>Data Penilaian</li>
    <li>Proses Penilaian</li>
    <li>Lampiran-lampiran termasuk foto, lokasi obyek penilaian dan lainnya</li>
</ul>
<p>Laporan Penilaian akan disampaikan dalam 2 (dua) rangkap.</p>

{{-- ===================== BATASAN TANGGUNG JAWAB ===================== --}}
<div class="h">Batasan atau Pengecualian atas Tanggung Jawab kepada Pihak selain Pemberi Tugas</div>
<p>
    Sepanjang sesuai dengan ketentuan peraturan perundang-undangan yang berlaku, Penilai tidak
    mempunyai kewajiban maupun tanggung jawab kepada pihak manapun selain Pemberi Tugas/Pengguna
    Laporan yang ditetapkan.
</p>

{{-- ===================== PERNYATAAN KEBENARAN DATA ===================== --}}
<div class="h">Pernyataan Kebenaran Data dan Informasi yang Diberikan oleh Pemberi Tugas</div>
<p>Pemberi tugas menyatakan bahwa:</p>
<ol class="n">
    <li>
        Kami menerima data-data penilaian berupa; salinan legalitas tanah berupa copy sertifikat dan
        copy pajak bumi dan bangunan (PBB), copy Izin Mendirikan Bangunan (IMB)/Persetujuan Bangunan
        Gedung (PBG) dan dokumen lainnya yang terkait langsung objek penilaian.
    </li>
    <li>
        Seluruh informasi dan pernyataan baik secara lisan maupun tulisan serta dokumen baik dalam
        bentuk asli, foto copy dan/atau salinan yang kami sampaikan kepada KJPP Sugianto Prasodjo
        dan Rekan yang kemudian dituangkan dalam bentuk laporan adalah benar-benar berasal dari
        pemberi tugas, akurat, lengkap dan sesuai dengan keadaan yang sebenarnya serta tidak
        mengalami perubahan lagi sampai dengan dikeluarkannya laporan tersebut.
    </li>
    <li>
        Jika terjadi kesalahan penyampaian informasi atas dokumen baik dalam bentuk asli maupun foto
        copy, pernyataan dan keterangan baik lisan maupun tertulis dari Pemberi Tugas yang
        menyebabkan kesalahan dalam analisa dan perhitungan penilaian; maka laporan penilaian
        menjadi tidak berlaku dan KJPP Sugianto Prasodjo dan Rekan beserta pimpinan, seluruh rekan
        dan staf baik yang bertandatangan maupun yang tidak bertandatangan di dalam laporan
        penilaian dibebaskan dari tuntutan perdata dan/atau pidana atas kerugian yang timbul baik
        secara langsung maupun tidak langsung.
    </li>
    <li>
        Pemberi Tugas wajib dan bersedia memberikan surat pernyataan tertulis yang memuat substansi
        sebagaimana disebutkan di atas, yang dibuat terpisah dari surat penawaran ini serta
        ditandatangani di atas meterai yang berlaku.
    </li>
</ol>

{{-- ===================== PENDEKATAN YANG DIGUNAKAN ===================== --}}
<div class="h">Pendekatan yang Digunakan</div>
<p>
    Pendekatan yang akan digunakan dalam penilaian ini adalah salah satu atau dua dari pendekatan
    yang ada sesuai dengan data-data / atau yang ada di lapangan, diantaranya:
</p>
<ul class="d">
    <li>
        <strong>Pendekatan Pasar (Market Approach)</strong>, Pendekatan Pasar memberikan indikasi
        nilai dengan membandingkan aset dengan aset lainnya yang identik atau sebanding dimana
        terdapat informasi harga. (SPI 106 3.12 - Pendekatan dan Metode Penilaian)
    </li>
    <li>
        <strong>Pendekatan Pendapatan (Income Approach)</strong>, Pendekatan Pendapatan memberikan
        indikasi nilai dengan mengkonversi arus kas masa depan menjadi satu nilai saat ini. Pada
        Pendekatan Pendapatan, nilai aset ditentukan dengan referensi kepada pendapatan arus kas
        atau penghematan biaya yang dihasilkan aset. (SPI 106 3.11 - Pendekatan dan Metode
        Penilaian)
    </li>
    <li>
        <strong>Pendekatan Biaya (Cost Approach)</strong>, Pendekatan Biaya memberikan indikasi
        nilai menggunakan prinsip ekonomi bahwa pembeli akan membayar aset tidak lebih dari biaya
        untuk mendapatkan aset dengan utilitas yang sama, baik melalui pembelian atau dengan
        pembuatan konstruksi dengan mengecualikan factor-faktor seperti waktu yang tidak semestinya,
        ketidaknyamanan, risiko atau factor-faktor lainnya. (SPI 106 3.10 - Pendekatan dan Metode
        Penilaian)
    </li>
</ul>

{{-- ===================== KONDISI PEMBATAS ===================== --}}
<div class="h">Kondisi Pembatas Penilaian</div>
<p>
    Berdasarkan identifikasi awal, kondisi pembatas dalam pelaksanaan penilaian ini meliputi
    keterbatasan pada tujuan dan ruang lingkup penugasan, serta terbatasnya verifikasi fisik objek
    penilaian. Penilaian ini tidak mencakup uji teknis detail dan hanya didasarkan pada
    data/informasi yang tersedia, dokumen yang disampaikan oleh Pemberi Tugas, serta hasil inspeksi
    lapangan.
</p>

{{-- ===================== DATA YANG DIPERLUKAN ===================== --}}
<div class="h">Data-data yang diperlukan :</div>
<ul class="d">
    @if ($purpose === 'Pelaporan Keuangan')
        <li>List Objek Penilaian sesuai dengan Laporan Keuangan.</li>
    @endif
    <li>Copy legalitas / sertifikat tanah (lembaran lengkap sesuai aslinya)</li>
    <li>Copy IMB/PBG</li>
    <li>Copy PBB (NJOP Tahun Terakhir)</li>
    <li>Gambar Lay Out Bangunan</li>
    <li>Copy NPWP</li>
    <li>
        Surat Pernyataan Kebenaran Data yang merupakan bagian yang tidak terpisahkan dari kontrak
        kerja.
    </li>
</ul>

{{-- ===================== PROSEDUR PELAKSANAAN ===================== --}}
<div class="h">Prosedur Pelaksanaan Penugasan</div>
<p>Penugasan penilaian ini akan dilakukan menurut prosedur dengan tahapan-tahapan sebagai berikut :</p>
<ol class="n">
    <li>Pengumpulan data awal.</li>
    <li>
        Pemeriksaan dan penelitian lapangan, untuk memperoleh data akurat tentang spesifikasi dan
        kondisi sebenarnya dari obyek penugasan.
    </li>
    <li>Penentuan kondisi terlihat, guna menentukan kondisi obyek penilaian.</li>
    <li>Penentuan pendekatan penilaian yang akan digunakan.</li>
    <li>Penetapan nilai aset dan penyampaian resume penilaian.</li>
    <li>Penyusunan laporan final penilaian properti.</li>
</ol>

{{-- ===================== PEMBATALAN PENUGASAN ===================== --}}
<div class="h">Pembatalan Penugasan</div>
<p>
    Pembatalan penugasan secara sepihak oleh Pemberi Tugas tidak membebaskan Pemberi Tugas dari
    kewajiban-kewajiban terhadap Penilai. Tujuan penugasan tidak memiliki hubungan ataupun
    kepentingan dengan pekerjaan yang dilakukan oleh Penilai dan tidak dapat dijadikan alasan oleh
    Pemberi Tugas untuk pembatalan penugasan. Apabila inspeksi lapangan tidak dapat dilakukan karena
    keberadaan objek penilaian tidak diketahui, maka biaya-biaya yang timbul karena proses inspeksi
    yang batal tersebut tetap kami perhitungkan.
</p>

{{-- ===================== KERAHASIAAN INFORMASI ===================== --}}
<div class="h">Kerahasiaan Informasi</div>
<p>
    KJPP Sugianto Prasodjo dan Rekan akan menjaga kerahasiaan informasi yang diterima dan hanya
    menyampaikan informasi tersebut pada karyawan dan kuasanya yang berkepentingan dan tidak
    membocorkan informasi meliputi namun tidak terbatas pada informasi tentang dan menyangkut
    bisnis, perencanaan, data keuangan dan lain-lain kepada pihak ketiga termasuk afiliasi dan
    subsidiary.
</p>

{{-- ===================== PENDAMPING LAPANGAN ===================== --}}
<div class="h">Pendamping Lapangan</div>
<p>
    Pemberi tugas bersedia menyiapkan diri / memberikan kuasa pada orang yang bisa mewakili untuk
    menunjukkan lokasi dan mendampingi inspeksi lapangan. Apabila Pendamping Lapangan adalah orang
    lain selain Pemilik/Pemberi Tugas, verifikasi yang Penilai/Pelaksana Inspeksi lakukan melalui
    telepon dan/atau sms/wa kepada Pemilik/Pemberi Tugas untuk memastikan pendampingan tersebut
    dapat anggap sebagai pengganti surat kuasa dari Pemilik/Pemberi Tugas. Pendamping ini harus yang
    benar-benar mengetahui lokasi properti dan batas-batas properti, sehingga tidak akan salah dalam
    penentuan lokasi aset/properti yang dinilai. Dalam hal adanya kesalahan penunjukan lokasi baik
    yang ditunjukkan oleh Pemilik/Pemberi Tugas maupun Pendamping Lapangan, maka hal ini menjadi
    tanggung jawab sepenuhnya pemberi tugas.
</p>

{{-- ===================== BERITA ACARA ===================== --}}
<div class="h">Berita Acara</div>
<p>
    Setiap akhir dari inspeksi lapangan yang dilakukan oleh Team Penilai dan penunjuk lapangan
    (Counterpart) maka kedua pihak diwajibkan untuk menandatangani berita acara hasil pemeriksaan
    inspeksi lapangan.
</p>

{{-- ===================== BIAYA JASA PENILAIAN ===================== --}}
<div class="h">Biaya Jasa Penilaian</div>
<p>Untuk melaksanakan pekerjaan penilaian ini, Biaya Profesional Jasa Penilaian adalah sebesar:</p>
<p style="margin-bottom: 2px;"><strong>{{ $rp($fee) }}</strong></p>
<p><em>{{ $terbilangRp($fee) }}</em></p>
<p>Biaya belum/sudah termasuk PPN yang berlaku, Transportasi, Akomodasi.</p>

<p class="sub" style="margin-bottom: 2px;">Termin Pembayaran :</p>
<ol class="n">
    <li>
        50% (lima puluh persen) sebesar {{ $rp($termin) }} {{ $terbilangRp($termin) }}, dibayarkan
        sebelum dilakukan inspeksi lapangan.
    </li>
    <li>
        50% (lima puluh persen) sebesar {{ $rp($fee - $termin) }} {{ $terbilangRp($fee - $termin) }},
        dibayarkan sebelum laporan final diserahkan.
    </li>
</ol>

<p class="sub" style="margin-bottom: 2px;">Rekening Bank :</p>
<table class="kv" style="margin-bottom: 6px;">
    <tr><td class="k">Bank</td><td class="s">:</td><td>{{ $bank['bank_name'] ?? '------------' }} &nbsp;&nbsp; NPWP No. {{ $cfg['npwp'] }}</td></tr>
    <tr><td class="k">Atas Nama</td><td class="s">:</td><td>{{ $bank['account_name'] ?? 'KJPP Sugianto Prasodjo dan Rekan' }}</td></tr>
    <tr><td class="k">No. Rek</td><td class="s">:</td><td>{{ $bank['account_number'] ?? '------------' }}</td></tr>
</table>
<p>
    Apabila terjadi pembatalan penugasan, pembayaran yang sudah dibayarkan kepada KJPP/Penilai tidak
    dapat dikembalikan.
</p>

{{-- ===================== PERNYATAAN PEMBERI TUGAS ===================== --}}
<div class="h">Pernyataan Pemberi Tugas</div>
<p>
    Pemberi tugas menyatakan bahwa aset yang dinilai tidak sedang atau telah dinilai oleh Penilai
    Publik lainnya untuk maksud, tujuan, pengguna laporan, dan tanggal penilaian yang sama atau
    berdekatan (dalam jangka waktu tidak lebih dari dua bulan). (KEPI 5.8 C.4)
</p>
<p>
    Jika disetujui, proposal ini berlaku sebagai SPK efektif pada tanggal penandatanganan. Mohon
    tanda tangan di kolom persetujuan dan paraf pada setiap halaman. Jika dokumen memuat barcode/QR
    KJPP SPR, keabsahan diverifikasi melalui pemindaian dan sah bila data yang tampil identik. Jika
    tanpa barcode/QR, keabsahan ditentukan oleh tanda tangan &amp; paraf serta kesesuaian identitas
    dokumen. Perubahan/penggantian/penambahan halaman tanpa persetujuan tertulis KJPP Sugianto
    Prasodjo &amp; Rekan membatalkan keabsahan. Masa berlaku penawaran 1 (satu) bulan kalender sejak
    tanggal proposal ini (setelahnya ketentuan/biaya dapat ditinjau kembali).
</p>

{{-- ===================== TANDA TANGAN ===================== --}}
<table class="sign mt" style="margin-top: 24px;">
    <tr>
        <td>
            Hormat kami,<br>
            <strong>{{ $cfg['company_name'] }}</strong><br>
            {{ $cfg['company_tagline'] }}
            <div class="gap"></div>
            <div class="line"></div>
            <strong>{{ $sig['name'] ?: 'Nama Penilai Publik' }}, MAPPI (Cert.)</strong><br>
            {{ $sig['partner_title'] }}<br>
            Penilai Properti Izin Menkeu No. : {{ $sig['izin_pp_no'] ?: '----' }}<br>
            MAPPI No. {{ $sig['mappi_no'] ?: '----' }}<br>
            RMK-2017.{{ $sig['rmk_no'] ?: '----' }}<br>
            (Tambahkan no izin lainnya jika ada)
        </td>
        <td>
            Menyetujui,<br>
            <strong>{{ $pt->client_name }}</strong>
            <div class="gap"></div>
            ( _______________________________ )<br>
            Jabatan:<br>
            Tanggal:
        </td>
    </tr>
</table>

@if ($project->shows_bank_acknowledgement)
    <table class="sign" style="margin-top: 22px;">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>{{ $pt->client_name }}</strong>
                <div class="gap"></div>
                ( _______________________________ )<br>
                Jabatan:<br>
                Tanggal:
            </td>
            <td></td>
        </tr>
    </table>
@endif

</body>
</html>
