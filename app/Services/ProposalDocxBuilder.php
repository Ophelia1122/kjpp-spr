<?php

namespace App\Services;

use App\Helpers\Terbilang;
use App\Models\Project;
use PhpOffice\PhpWord\ComplexType\TblWidth as TblWidthComplexType;
use PhpOffice\PhpWord\Element\Footer;
use PhpOffice\PhpWord\Element\Header;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth as TblWidthSimpleType;

/**
 * Membangun dokumen .docx "Surat Penawaran Jasa Penilaian" mengikuti
 * FORMAT BAKU master proposal KJPP SPR.
 *
 * .docx ini adalah MASTER (bisa diedit staf untuk penyesuaian SPM).
 * PDF dihasilkan dari .docx ini via LibreOffice (App\Services\DocxToPdf),
 * sehingga Word & PDF selalu identik.
 *
 * Seluruh KALIMAT BAKU diambil dari config/proposal_clauses.php.
 * Struktur & logika kondisional per jenis proposal ada di sini.
 *
 * OVERRIDE TEKS PER-BAB (Batch 3): kalau proyek punya baris di
 * proposal_section_texts untuk sebuah bab, teks itu dipakai menggantikan
 * teks baku bab tsb (lihat bodyOr()). Tabel & elemen struktural bab tetap
 * dibuat otomatis. Bab tanpa override = perilaku lama, byte-identik.
 */
class ProposalDocxBuilder
{
    private PhpWord $word;
    private Section $s;
    private array $cl;          // config('proposal_clauses')
    private array $cfg;         // config('kjpp')
    private int $secNo = 0;

    private array $pJustify;
    private array $fBody;
    private array $fBold;
    private string $fName = 'Arial';
    private float $fSize = 10;
    private ?array $sigCache = null;

    /** [section_key => body] override manual teks bab (proposal_section_texts). */
    private array $overrides = [];

    // true tepat setelah sebuah heading di-emit: paragraf isi PERTAMA
    // sesudahnya dibuat "keepLines" supaya heading tidak menggantung
    // sendirian di dasar halaman (isi ikut pindah ke halaman berikutnya).
    private bool $keepWithHeading = false;

    // Nomor daftar bernomor DALAM satu bab. Di-reset ke 0 tiap kali
    // masuk bab baru (lihat sectionTitle) supaya penomoran mulai dari 1
    // lagi, tidak lanjut dari bab sebelumnya.
    private int $listNo = 0;

    // Indent kiri (twip) untuk SELURUH isi bab — supaya isi sejajar dengan
    // huruf pertama judul bab, bukan dengan nomornya. = kira-kira lebar
    // "N. ". Di-set di sectionTitle(), 0 di luar bab.
    private int $bodyIndent = 0;

    // Tabel daftar bernomor yang sedang dibangun (lihat openListRow/closeList).
    private $listTbl = null;
    private ?int $listKey = null;

    /**
     * Katalog bab yang bisa di-override teksnya, DALAM URUTAN DOKUMEN.
     * - editable=false : bab isinya murni tabel/struktur, tak ada prosa
     *   untuk diedit (tetap ditampilkan di editor sebagai info).
     * - only           : bab hanya relevan untuk jenis proposal tertentu.
     * Judul HARUS sama dengan argumen sectionTitle() terkait.
     */
    private const SECTION_META = [
        'pembuka' => [
            'title'    => 'Kalimat Pembuka Surat',
            'editable' => true,
            'note'     => 'Paragraf pembuka sebelum bab 1. Isian "Dasar Permintaan Penilaian" ikut ter-render di sini.',
        ],
        'status_penilai' => [
            'title'    => 'Penjelasan Status Penilai',
            'editable' => true,
            'note'     => 'Nomor izin, izin Penilai Pertanahan & sektor OJK diambil dari biodata penandatangan. Tampil sebagai poin; bila diedit, tiap blok jadi paragraf biasa.',
        ],
        'pemberi_tugas' => [
            'title'    => 'Pemberi Tugas',
            'editable' => false,
            'note'     => 'Satu paragraf nama + alamat Pemberi Tugas, dibuat otomatis dari data klien. Tidak ada teks prosa untuk diedit.',
        ],
        'pengguna_laporan' => [
            'title'    => 'Pengguna Laporan',
            'editable' => false,
            'note'     => 'Satu paragraf nama + alamat seluruh Pengguna Laporan, dibuat otomatis dari data klien.',
        ],
        'pengguna_laporan_lk' => [
            'title'    => 'Pengguna Laporan — Kalimat KAP/Auditor',
            'editable' => true,
            'note'     => 'Kalimat tambahan khusus proposal Pelaporan Keuangan, tercetak setelah daftar Pengguna Laporan.',
            'only'     => Project::PURPOSE_LK_PROPERTI,
        ],
        'objek' => [
            'title'    => 'Identifikasi Obyek Penilaian dan Kepemilikan',
            'editable' => true,
            'note'     => 'Kalimat pembuka & tabel objek dibuat otomatis dari data proyek. Yang diedit di sini = 2 paragraf penjelasan SETELAH tabel.',
        ],
        'mata_uang' => [
            'title'    => 'Jenis Mata Uang yang Digunakan',
            'editable' => true,
            'note'     => '',
        ],
        'maksud_tujuan' => [
            'title'    => 'Maksud dan Tujuan Penilaian',
            'editable' => true,
            'note'     => 'Label tebal "Maksud Penilaian:" / "Tujuan Penilaian:" jadi teks biasa bila bab ini diedit.',
        ],
        'dasar_nilai' => [
            'title'    => 'Dasar Nilai',
            'editable' => true,
            'note'     => 'Sub-judul tebal (NILAI PASAR / NILAI WAJAR / NILAI LIKUIDASI) jadi teks biasa bila bab ini diedit.',
        ],
        'waktu_ekspos' => [
            'title'    => 'Waktu Ekspos (Exposure Time)',
            'editable' => true,
            'note'     => 'Bab tersendiri, hanya untuk proposal Lelang.',
            'only'     => Project::PURPOSE_LELANG,
        ],
        'tanggal_penilaian' => [
            'title'    => 'Tanggal Penilaian',
            'editable' => true,
            'note'     => 'Tanggal penilaian sudah ter-render dari data proyek.',
        ],
        'tki' => [
            'title'    => 'Tingkat Kedalaman Investigasi',
            'editable' => true,
            'note'     => 'Satu poin per baris (tekan Enter biasa); baris berawalan a. b. c. otomatis jadi daftar rapi. Penanda huruf (a., b., …) jadi teks biasa bila diedit. Blok tambahan per jenis aset (bangunan/mesin/kendaraan/alat berat) ikut ter-render di kotak teks.',
        ],
        'sifat_sumber' => [
            'title'    => 'Sifat dan Sumber Informasi yang Dapat Diandalkan',
            'editable' => true,
            'note'     => '',
        ],
        'asumsi' => [
            'title'    => 'Asumsi Umum dan Asumsi Khusus',
            'editable' => true,
            'note'     => 'Satu poin per baris (tekan Enter biasa); baris berawalan a. b. c. otomatis jadi daftar rapi. Penanda huruf jadi teks biasa bila diedit.',
        ],
        'publikasi' => [
            'title'    => 'Persyaratan atas Persetujuan untuk Publikasi',
            'editable' => true,
            'note'     => '',
        ],
        'konfirmasi_spi' => [
            'title'    => 'Konfirmasi bahwa Penilaian dilakukan Berdasarkan SPI',
            'editable' => true,
            'note'     => '',
        ],
        'laporan' => [
            'title'    => 'Laporan Penilaian',
            'editable' => true,
            'note'     => 'Jangka waktu SLA sudah ter-render dari data proyek. Satu poin per baris (tekan Enter biasa); baris berawalan a. b. c. otomatis jadi daftar rapi.',
        ],
        'batasan_tanggung_jawab' => [
            'title'    => 'Batasan atau Pengecualian atas Tanggung Jawab kepada Pihak selain Pemberi Tugas',
            'editable' => true,
            'note'     => '',
        ],
        'kebenaran_data' => [
            'title'    => 'Pernyataan Kebenaran Data dan Informasi yang Diberikan oleh Pemberi Tugas',
            'editable' => true,
            'note'     => 'Satu poin per baris (tekan Enter biasa); baris berawalan a. b. c. otomatis jadi daftar rapi.',
        ],
        'pendekatan' => [
            'title'    => 'Pendekatan yang Digunakan',
            'editable' => true,
            'note'     => 'Nama pendekatan yang tadinya tebal jadi teks biasa bila bab ini diedit. Satu poin per baris (tekan Enter biasa); baris berawalan a. b. c. otomatis jadi daftar rapi.',
        ],
        'kondisi_pembatas' => [
            'title'    => 'Kondisi Pembatas Penilaian',
            'editable' => true,
            'note'     => '',
        ],
        'prosedur' => [
            'title'    => 'Prosedur Pelaksanaan Penugasan',
            'editable' => true,
            'note'     => 'Satu tahap per baris (tekan Enter biasa); baris berawalan a. b. c. otomatis jadi daftar rapi.',
        ],
        'pembatalan' => [
            'title'    => 'Pembatalan Penugasan',
            'editable' => true,
            'note'     => '',
        ],
        'kerahasiaan' => [
            'title'    => 'Kerahasiaan Informasi',
            'editable' => true,
            'note'     => '',
        ],
        'pendamping' => [
            'title'    => 'Pendamping Lapangan',
            'editable' => true,
            'note'     => '',
        ],
        'berita_acara' => [
            'title'    => 'Berita Acara',
            'editable' => true,
            'note'     => '',
        ],
        'biaya' => [
            'title'    => 'Biaya Jasa Penilaian',
            'editable' => true,
            'note'     => 'Termasuk kalimat status PPN. Nominal, terbilang, rincian, termin, rekening bank & kalimat pembatalan pembayaran dibuat otomatis.',
        ],
        'pernyataan_pemberi_tugas' => [
            'title'    => 'Pernyataan Pemberi Tugas',
            'editable' => true,
            'note'     => 'Termasuk klausul penutup "proposal berlaku sebagai SPK".',
        ],
        'lampiran' => [
            'title'    => 'Lampiran Permintaan Data',
            'editable' => true,
            'note'     => 'Halaman terakhir dokumen. Satu poin per baris (tekan Enter biasa); baris berawalan a. b. c. otomatis jadi daftar rapi.',
        ],
    ];

    // Regex istilah Inggris (miring) & sumber/standar (tebal) — dibangun
    // sekali dari config('proposal_clauses.text_style').
    private string $italicRe = '';
    private string $sourceRe = '';

    public function __construct(private Project $project)
    {
        $this->project->loadMissing('instructingClient', 'intendedUsers', 'valuationObjects', 'signedBy', 'sectionTexts', 'bank');
        $this->cl  = config('proposal_clauses');
        $this->cfg = config('kjpp');
        $this->overrides = $this->project->sectionTexts->pluck('body', 'section_key')->all();

        // Delimiter '/' ikut di-escape (istilah bisa memuat "/", mis. "ketentuan/biaya").
        $it = array_map(fn ($x) => preg_quote($x, '/'), $this->cl['text_style']['italic'] ?? []);
        $bd = array_map(fn ($x) => preg_quote($x, '/'), $this->cl['text_style']['bold'] ?? []);
        // istilah terpanjang dulu supaya "Market Value" tidak kalah oleh frasa lebih pendek
        usort($it, fn ($a, $b) => strlen($b) <=> strlen($a));
        $this->italicRe = $it ? '/(?<![\p{L}])(?:' . implode('|', $it) . ')(?![\p{L}])/u' : '';
        $this->sourceRe = $bd ? '/(?<![\p{L}])(?:' . implode('|', $bd) . ')(?![\p{L}])/u' : '';
    }

    public static function for(Project $project): self
    {
        return new self($project);
    }

    /** Bangun .docx, simpan ke file sementara, kembalikan path-nya. */
    public function save(): string
    {
        $this->build();

        $path = storage_path('app/tmp/' . $this->safeName() . '-' . uniqid() . '.docx');
        @mkdir(dirname($path), 0775, true);

        try {
            \PhpOffice\PhpWord\IOFactory::createWriter($this->word, 'Word2007')->save($path);
        } finally {
            foreach ($this->tempFiles as $tmp) {
                @unlink($tmp);
            }
        }

        return $path;
    }

    /**
     * Nama file unduhan: "00246 - Pnw_PT Bank ABC" (2026-09-21, feedback
     * user). 5 digit = nomor urut di depan nomor proposal, nol di depan
     * dipertahankan. Karakter terlarang di nama file Windows dibuang.
     */
    public function safeName(): string
    {
        $no = preg_match('/^\s*(\d+)/', (string) $this->project->proposal_number, $m)
            ? str_pad(substr($m[1], -5), 5, '0', STR_PAD_LEFT)
            : '00000';
        $client = $this->project->instructingClient?->client_name ?? 'Klien';
        $client = trim(preg_replace('/\s+/', ' ', preg_replace('#[\\\\/:*?"<>|]+#', ' ', $client)));

        return $no . ' - Pnw_' . $client;
    }

    // =====================================================================

    private function build(): void
    {
        // WAJIB: PhpWord tidak meng-escape teks secara default -> karakter
        // '&', '<', '>' pada nama klien/objek akan merusak XML .docx.
        Settings::setOutputEscapingEnabled(true);

        $this->fName = $this->cfg['pdf_font'] ?: 'Arial';
        $this->fSize = (float) ($this->cfg['pdf_font_size'] ?? 10);

        $this->word = new PhpWord();
        $this->word->setDefaultFontName($this->fName);
        $this->word->setDefaultFontSize($this->fSize);

        $this->fBody    = ['name' => $this->fName, 'size' => $this->fSize];
        $this->fBold    = ['name' => $this->fName, 'size' => $this->fSize, 'bold' => true];
        $this->pJustify = ['alignment' => Jc::BOTH, 'spaceAfter' => 120, 'spaceBefore' => 0];

        // Ukuran & margin halaman = A4 dengan margin baku kantor
        // (Top 2,75 / Bottom 2,54 / Left 2,54 / Right 2,54 cm, gutter 0).
        // Kop halaman-1 (logo panjang) lebih tinggi dari margin atas —
        // Word/LibreOffice otomatis menurunkan baris isi pertama.
        $this->s = $this->word->addSection([
            'pageSizeW'    => Converter::cmToTwip(21.0),
            'pageSizeH'    => Converter::cmToTwip(29.7),
            'marginTop'    => Converter::cmToTwip(2.75),
            'marginBottom' => Converter::cmToTwip(2.54),
            'marginLeft'   => Converter::cmToTwip(2.54),
            'marginRight'  => Converter::cmToTwip(2.54),
            'gutter'       => 0,
            'headerHeight' => Converter::cmToTwip(1.0),
            'footerHeight' => Converter::cmToTwip(1.0),
        ]);

        $this->buildHeaders();
        $this->buildFooters();

        $this->buildLetterHead();
        $this->sectionStatusPenilai();
        $this->sectionPemberiTugas();
        $this->sectionPenggunaLaporan();
        $this->sectionObjek();
        $this->sectionMataUang();
        $this->sectionMaksudTujuan();
        $this->sectionDasarNilai();
        $this->sectionTanggalPenilaian();
        $this->sectionTKI();
        $this->sectionSifatSumber();
        $this->sectionAsumsi();
        $this->sectionPublikasi();
        $this->sectionKonfirmasiSPI();
        $this->sectionLaporan();
        $this->sectionBatasanTanggungJawab();
        $this->sectionKebenaranData();
        $this->sectionPendekatan();
        $this->sectionKondisiPembatas();
        $this->sectionProsedur();
        $this->sectionTitle('Pembatalan Penugasan');
        $this->bodyOr('pembatalan', fn () => $this->para($this->cl['pembatalan']));
        $this->sectionTitle('Kerahasiaan Informasi');
        $this->bodyOr('kerahasiaan', fn () => $this->para($this->cl['kerahasiaan']));
        $this->sectionTitle('Pendamping Lapangan');
        $this->bodyOr('pendamping', fn () => $this->para($this->cl['pendamping']));
        $this->sectionTitle('Berita Acara');
        $this->bodyOr('berita_acara', fn () => $this->para($this->cl['berita_acara']));
        $this->sectionBiaya();
        $this->sectionPernyataanPemberiTugas();
        $this->sectionTandaTangan();
        $this->sectionLampiran();
    }

    // ---------- kop & footer ----------

    /**
     * Kop = gambar logo saja, tanpa teks tambahan.
     * Halaman 1  : logo-spr-long.png, rata tengah, diberi garis pembatas
     *              di bawahnya (pemisah kop & isi).
     * Halaman 2+ : logo-spr-short.png, rata KIRI, tanpa garis.
     */
    private function buildHeaders(): void
    {
        $this->headerImage($this->s->addHeader(Header::FIRST), 'logo-spr-long.png', 12.0, 1029 / 242, Jc::CENTER, true);
        $this->headerImage($this->s->addHeader(), 'logo-spr-short.png', 8.0, 855 / 187, Jc::START, false);
    }

    private function headerImage(Header $h, string $file, float $widthCm, float $ratio, string $align, bool $rule): void
    {
        $path = public_path('images/' . $file);
        if (!is_file($path)) {
            return;
        }
        $w = Converter::cmToPoint($widthCm);
        $tr = $h->addTextRun(['alignment' => $align, 'spaceAfter' => 0, 'spaceBefore' => 0]);
        $tr->addImage($path, ['width' => $w, 'height' => $w / $ratio]);

        if ($rule) {
            // Garis pembatas horizontal = paragraf kosong dengan border bawah.
            $h->addText('', ['size' => 2], [
                'spaceBefore'       => 60,
                'spaceAfter'        => 0,
                'borderBottomSize'  => 8,
                'borderBottomColor' => '000000',
            ]);
        }
    }

    /**
     * Footer halaman 1 = blok alamat kantor (rata tengah), tanpa nomor
     * halaman. Halaman 2+ = gambar footer-proposal.png, lalu nomor
     * halaman (angka saja) rata tengah di bawahnya.
     */
    private function buildFooters(): void
    {
        $center = ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0];

        $first = $this->s->addFooter(Footer::FIRST);
        $small = ['name' => $this->fName, 'size' => 6.5, 'color' => '333333'];
        foreach ($this->cfg['footer']['lines'] as $line) {
            $first->addText($line, $small, $center);
        }

        $rest = $this->s->addFooter();
        $bar  = public_path('images/footer-proposal.png');
        if (is_file($bar)) {
            $w = Converter::cmToPoint(16.6);
            $tr = $rest->addTextRun($center);
            $tr->addImage($bar, ['width' => $w, 'height' => $w / (1973 / 32)]);
        }
        $rest->addPreserveText('{PAGE}', ['name' => $this->fName, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceBefore' => 40, 'spaceAfter' => 0]);
    }

    private function buildLetterHead(): void
    {
        $t = $this->s->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
        $t->addRow();
        $t->addCell(Converter::cmToTwip(10))->addText('No. ' . $this->project->proposal_number, $this->fBold, ['spaceAfter' => 0]);
        $t->addCell(Converter::cmToTwip(6.5))->addText('Jakarta, ' . $this->idDate($this->project->effective_proposal_date), $this->fBold, ['alignment' => Jc::RIGHT, 'spaceAfter' => 0]);

        $this->s->addTextBreak(1);
        $pt = $this->project->instructingClient;
        $this->s->addText('Kepada Yth,', $this->fBody, ['spaceAfter' => 0]);
        $this->s->addText($pt->client_name, $this->fBold, ['spaceAfter' => 0]);
        foreach ($this->addressLines($pt->address) as $ln) {
            $this->s->addText($ln, $this->fBody, ['spaceAfter' => 0]);
        }

        $this->s->addTextBreak(1);
        // "an." = Nama Klien (isian manual), jatuh ke Pemberi Tugas bila kosong.
        $this->s->addText('Hal : Proposal Biaya Jasa Penilaian an. ' . $this->project->effective_client_name, $this->fBold, ['spaceAfter' => 120]);
        $this->s->addText('Dengan hormat,', $this->fBody, ['spaceAfter' => 120]);

        $this->bodyOr('pembuka', fn () => $this->para($this->pembukaBaku()));
    }

    private function pembukaBaku(): string
    {
        $basis = trim((string) $this->project->request_basis) ?: $this->cl['pembuka_basis_placeholder'];

        return strtr($this->cl['pembuka'], [
            ':basis' => $basis,
            ':klien' => $this->project->instructingClient->client_name,
        ]);
    }

    // ---------- sections ----------

    /**
     * Data penandatangan proposal untuk Blok Tanda Tangan & Penjelasan
     * Status Penilai. Kalau proposal punya penandatangan terpilih
     * (signed_by_user_id, jabatan "Penanggung Jawab"), ambil dari biodata
     * user tsb — field yang kosong jatuh ke config('kjpp.signatory').
     * Tanpa penandatangan terpilih = sepenuhnya config (perilaku lama).
     */
    private function signatory(): array
    {
        if ($this->sigCache !== null) {
            return $this->sigCache;
        }

        $cfg = $this->cfg['signatory'];
        // STTD OJK dipakai di dua tempat dengan format berbeda (2026-09-15,
        // feedback user): blok tanda tangan = NOMOR saja ('sttd_ojk_no'),
        // kalimat Penjelasan Status Penilai = nomor + tanggal ('sttd_ojk_full').
        $cfg['sttd_ojk_full'] = $cfg['sttd_ojk_no'];
        $cfg['sttd_ojk_no']   = trim((string) preg_replace('/\s+tanggal\s+.*$/i', '', (string) $cfg['sttd_ojk_no']));
        $u   = $this->project->signedBy;

        if (! $u) {
            return $this->sigCache = $cfg;
        }

        return $this->sigCache = [
            'name'         => $u->name ?: $cfg['name'],
            // Status di perusahaan (mis. "Partner" / "Managing Partner") —
            // beda per orang, diambil dari biodata user, bukan hardcode.
            // Jabatan internal ("Penanggung Jawab") TIDAK pernah dicetak.
            'title'        => $u->partner_status ?: $cfg['title'],
            'izin_pp_no'   => $u->izin_menkeu_no ?: $cfg['izin_pp_no'],
            // Nomor + tanggal surat disimpan terpisah di biodata, digabung
            // saat cetak: "185/MK/SJ/2025 tanggal 23 April 2025".
            'sk_menkeu_no' => $u->licenseWithDate('sk_menkeu_no', 'sk_menkeu_date') ?: $cfg['sk_menkeu_no'],
            'sttd_ojk_no'   => trim((string) $u->sttd_ojk_no) ?: $cfg['sttd_ojk_no'],
            'sttd_ojk_full' => $u->licenseWithDate('sttd_ojk_no', 'sttd_ojk_date') ?: $cfg['sttd_ojk_full'],
            'mappi_no'     => $u->mappi_no ?: $cfg['mappi_no'],
            'rmk_no'       => $u->rmk_no ?: $cfg['rmk_no'],
            'klasifikasi'  => $u->klasifikasi ?: $cfg['klasifikasi'],
            // Bab Penjelasan Status Penilai versi poin (2026-09-22).
            'pertanahan'   => $u->licenseWithDate('pertanahan_izin_no', 'pertanahan_izin_date'),
            'ojk_sectors'  => $u->ojk_sectors ?: null,
        ];
    }

    /**
     * "185/MK/SJ/2025 tanggal 23 April 2025" -> "**185/MK/SJ/2025** tanggal
     * **23 April 2025**" (contoh proposal resmi 02309). $lead ikut ditebalkan
     * di depan nomor, mis. "Kepmenkeu Nomor: ".
     */
    private function boldLicense(?string $value, string $lead = ''): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^(.*?)\s+tanggal\s+(.+)$/iu', $value, $m)) {
            return '**' . $lead . $m[1] . '** tanggal **' . $m[2] . '**';
        }

        return '**' . $lead . $value . '**';
    }

    /** Placeholder -> nilai untuk kalimat "Penjelasan Status Penilai". */
    private function statusPenilaiRepl(): array
    {
        $sig = $this->signatory();

        return [
            ':nama'        => $sig['name'],
            // "Klasifikasi Bidang Jasa Properti dan Bisnis" -> "Penilai Publik Properti dan Bisnis".
            ':jenis'       => 'Penilai Publik ' . (trim((string) preg_replace('/^\s*klasifikasi\s+bidang\s+jasa\s+/i', '', (string) $sig['klasifikasi'])) ?: 'Properti'),
            ':pertanahan'  => $this->boldLicense($sig['pertanahan'] ?? null),
            ':izin'        => $sig['izin_pp_no'],
            ':sk_menkeu'   => $this->boldLicense($sig['sk_menkeu_no']),
            // Nomor KEP Dewan Komisioner OJK = nomor Surat Tanda Terdaftar OJK
            // penilai (satu nomor yang sama, 2026-09-15 feedback user).
            ':ojk_kep'     => $this->boldLicense($sig['sttd_ojk_full']),
            ':izin_usaha'  => $this->cfg['izin_usaha_no'],
            ':kepmenkeu'   => $this->boldLicense($this->cfg['kepmenkeu_no'], 'Kepmenkeu Nomor: '),
            ':sttd_ojk'    => $this->boldLicense($this->cfg['sttd_ojk_no'], 'Surat Tanda Terdaftar Profesi Penunjang Pasar Modal Nomor: '),
        ];
    }

    /**
     * Poin-poin bab Penjelasan Status Penilai (2026-09-22): key => teks, sudah
     * diisi data. Poin pertanahan dibuang bila penandatangan tidak punya izin.
     */
    private function statusPenilaiPoin(): array
    {
        $repl = $this->statusPenilaiRepl();
        $poin = array_map(fn ($t) => strtr($t, $repl), $this->cl['status_penilai_poin']);
        if (blank($this->signatory()['pertanahan'] ?? null)) {
            unset($poin['pertanahan']);
        }

        return $poin;
    }

    /** Sektor OJK penandatangan; kosong = 4 sektor baku (tanpa Pasar Modal). */
    private function ojkSectors(): array
    {
        $list = $this->signatory()['ojk_sectors'] ?? null;

        return $list ?: array_values(array_filter(
            \App\Models\User::OJK_SECTORS, fn ($x) => ! str_starts_with($x, 'Pasar Modal')
        ));
    }

    private function sectionStatusPenilai(): void
    {
        $this->sectionTitle('Penjelasan Status Penilai');
        $this->bodyOr('status_penilai', function () {
            // Daftar poin "•" (bukan paragraf) mengikuti dokumen resmi; sektor
            // OJK jadi sub-daftar bernomor di bawah poin OJK.
            foreach ($this->statusPenilaiPoin() as $key => $text) {
                $this->bulletItem($text);
                if ($key === 'ojk') {
                    $sectors = $this->ojkSectors();
                    foreach ($sectors as $i => $sector) {
                        $this->numberedSubItem($i + 1, $sector . ($i === count($sectors) - 1 ? '.' : ''));
                    }
                }
            }
        });
    }

    /**
     * "**PT A** yang beralamat di Jl. X" — satu pihak untuk paragraf bab
     * Pemberi Tugas / Pengguna Laporan (2026-09-22, feedback user).
     */
    private function partyPhrase($client): string
    {
        $name = '**' . mb_strtoupper((string) $client->client_name) . '**';
        $addr = rtrim($this->flatAddress($client->address), ' .');

        return $addr === '' ? $name : $name . ' yang beralamat di ' . $addr;
    }

    /** "A", "A dan B", "A, B dan C". */
    private function joinParties(array $phrases): string
    {
        if (count($phrases) <= 1) {
            return (string) ($phrases[0] ?? '');
        }
        $last = array_pop($phrases);

        return implode(', ', $phrases) . ' dan ' . $last;
    }

    private function sectionPemberiTugas(): void
    {
        $this->sectionTitle('Pemberi Tugas');
        $this->richPara('Pemberi Tugas adalah ' . $this->partyPhrase($this->project->instructingClient) . '.');
    }

    private function sectionPenggunaLaporan(): void
    {
        $this->sectionTitle('Pengguna Laporan');
        $phrases = $this->project->intendedUsers->map(fn ($u) => $this->partyPhrase($u))->all();
        $this->richPara('Pengguna laporan adalah ' . $this->joinParties($phrases) . '.');

        // Kalimat KAP/Auditor — khusus Pelaporan Keuangan.
        if ($this->project->proposal_purpose === Project::PURPOSE_LK_PROPERTI) {
            $this->bodyOr('pengguna_laporan_lk', fn () => $this->para($this->pgnLaporanLkBaku()));
        }
    }

    private function pgnLaporanLkBaku(): string
    {
        return strtr($this->cl['pengguna_laporan_lk_kap'], [
            ':klien' => $this->project->instructingClient->client_name,
        ]);
    }

    private function sectionObjek(): void
    {
        $this->sectionTitle('Identifikasi Obyek Penilaian dan Kepemilikan');
        // keepNext: kalimat pengantar ikut pindah halaman bersama tabelnya.
        $this->para('Obyek Penilaian dalam lingkup penugasan ini adalah :', null, ['keepNext' => true]);

        // Lebar tabel disamakan dengan lebar paragraf di atasnya: total
        // lebar kolom + indent = lebar area isi halaman, dan tabel digeser
        // ke kanan sejauh bodyIndent (sejajar huruf pertama paragraf, bukan
        // menempel margin halaman). Proporsi kolom asli (No/Jenis/Lokasi/
        // Bentuk/Atas Nama) dipertahankan, hanya diskalakan agar pas persis.
        // CATATAN: 'width' pct=100% (cara lama) TERNYATA diukur LibreOffice
        // terhadap lebar halaman PENUH, tidak dikurangi indent — makanya
        // dipakai lebar absolut (dxa) + 'layout' fixed di sini.
        $ratios  = [0.9, 4.6, 5.2, 3.4, 3.0]; // No | Jenis | Lokasi | Bentuk | Atas Nama
        $targetW = (int) Converter::cmToTwip(self::PAGE_CONTENT_W_CM) - $this->bodyIndent;
        $colW    = $this->distributeWidths($ratios, $targetW);

        $tbl = $this->s->addTable([
            'borderSize' => 6, 'borderColor' => '000000',
            'width'      => $targetW, 'unit' => TblWidthSimpleType::TWIP,
            'cellMargin' => 60,
            'indent'     => new TblWidthComplexType($this->bodyIndent, TblWidthSimpleType::TWIP),
            'layout'     => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
        ]);
        // Header berlatar biru muda, huruf 11 pt tebal (2026-09-21, feedback
        // user, meniru format dokumen resmi KJPP). Semua teks rata tengah.
        // Baris pertama kolom "Jenis Aset/Properti" (kategori) dibuat tebal.
        // keepNext di tiap paragraf sel (kecuali baris terakhir) membuat
        // tabel tidak terpotong: kalau tidak muat, seluruh tabel pindah ke
        // halaman berikutnya bersama judul bab.
        $hd     = ['bold' => true, 'size' => 11];
        $cd     = ['size' => 11];
        $cdBold = ['size' => 11, 'bold' => true];
        $hdCell = ['bgColor' => 'C6D9F1', 'valign' => 'center'];
        $bdCell = ['valign' => 'center'];
        $pKeep  = ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'keepNext' => true, 'keepLines' => true];
        $pLast  = ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'keepLines' => true];

        $objects = $this->project->valuationObjects;
        $tbl->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        foreach (['No.', 'Jenis Aset/Properti', 'Lokasi', 'Bentuk/Jenis Hak Atas Tanah', 'Atas Nama'] as $c => $label) {
            $tbl->addCell($colW[$c], $hdCell)->addText($label, $hd, $objects->isEmpty() ? $pLast : $pKeep);
        }

        foreach ($objects as $i => $o) {
            $pc = $i === $objects->count() - 1 ? $pLast : $pKeep;
            $tbl->addRow(null, ['cantSplit' => true]);
            $this->cellLines($tbl->addCell($colW[0], $bdCell), (string) ($i + 1), $cd, $pc);
            $jenis = $tbl->addCell($colW[1], $bdCell);
            foreach ($o->description_lines as $k => $ln) {
                $this->cellLines($jenis, $ln, $k === 0 ? $cdBold : $cd, $pc);
            }
            $this->cellLines($tbl->addCell($colW[2], $bdCell), $o->location, $cd, $pc);
            $this->cellLines($tbl->addCell($colW[3], $bdCell), $o->ownership_form, $cd, $pc);
            $this->cellLines($tbl->addCell($colW[4], $bdCell), $o->owner_name, $cd, $pc);
        }

        $this->s->addTextBreak(1);

        $this->bodyOr('objek', function () {
            $this->para(strtr($this->cl['post_objek_hubungan'], $this->objekPenutupRepl()));
            $this->para($this->cl['post_objek']);
        });
    }

    /**
     * Isi sel tabel, satu paragraf per baris. Teks dari textarea membawa
     * CRLF; kalau karakter CR ikut masuk ke XML, LibreOffice gagal membaca
     * tabel dan mencetak semua sel bertumpuk tanpa garis (bug 2026-09-21).
     */
    private function cellLines($cell, ?string $text, array $font, array $pStyle): void
    {
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $text)), 'strlen') ?: [''];
        foreach ($lines as $ln) {
            $cell->addText($ln, $font, $pStyle);
        }
    }

    /**
     * Bagi $totalTwip ke tiap kolom sebanding dengan $ratios (bebas skala,
     * tak perlu berjumlah 1). Selisih pembulatan ditaruh di kolom TERAKHIR
     * supaya total akhirnya presisi sampai twip (tabel tidak meleset dari
     * $totalTwip walau 1 twip, yang bisa membuat sisi kanannya sedikit
     * lewat/kurang dari margin).
     */
    private function distributeWidths(array $ratios, int $totalTwip): array
    {
        $sum    = array_sum($ratios);
        $widths = array_map(fn ($r) => (int) round($totalTwip * $r / $sum), $ratios);
        $widths[array_key_last($widths)] += $totalTwip - array_sum($widths);

        return $widths;
    }

    /**
     * Lebar kolom (twip) untuk tabel yang harus SEJAJAR dengan paragraf di
     * atasnya (mis. rincian biaya, rekening bank, blok tanda tangan) —
     * proporsi $ratiosCm dipertahankan apa adanya kalau totalnya masih
     * muat, tapi dipangkas proporsional bila total + bodyIndent akan
     * melewati lebar isi halaman (tabel yang lebarnya sudah dekat lebar
     * halaman penuh, mis. Rekening Bank & blok tanda tangan).
     */
    private function indentedColWidths(array $ratiosCm): array
    {
        $natural = (int) Converter::cmToTwip(array_sum($ratiosCm));
        $cap     = (int) Converter::cmToTwip(self::PAGE_CONTENT_W_CM) - $this->bodyIndent;

        return $this->distributeWidths($ratiosCm, min($natural, $cap));
    }

    /** Properti tabel baku dipakai bareng dg indentedColWidths(): rata dg paragraf. */
    private function indentedTableStyle(int $totalWidthTwip, array $extra = []): array
    {
        return $extra + [
            'width'      => $totalWidthTwip, 'unit' => TblWidthSimpleType::TWIP,
            'indent'     => new TblWidthComplexType($this->bodyIndent, TblWidthSimpleType::TWIP),
            'layout'     => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'cellMargin' => 0,
        ];
    }

    private function objekPenutupRepl(): array
    {
        $ownerNames = $this->project->valuationObjects->pluck('owner_name')
            ->map(fn ($n) => trim((string) $n))->filter()->unique()->implode('; ');

        return [
            ':nama_sertifikat' => $ownerNames ?: '---nama pada sertifikat---',
            ':pemberi_tugas'   => $this->project->instructingClient->client_name,
        ];
    }

    private function sectionMataUang(): void
    {
        $this->sectionTitle('Jenis Mata Uang yang Digunakan');
        $this->bodyOr('mata_uang', fn () => $this->para($this->cl['mata_uang']));
    }

    private function sectionMaksudTujuan(): void
    {
        $this->sectionTitle('Maksud dan Tujuan Penilaian');
        $this->bodyOr('maksud_tujuan', function () {
            $parts = $this->maksudTujuanParts();
            $this->keepWithHeading = false;

            // Tabel 3 kolom (label | ":" | isi) supaya titik dua Maksud & Tujuan
            // benar-benar sejajar & baris lanjutan isi rapi.
            $t = $this->s->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
            foreach ([['Maksud Penilaian', $parts['maksud']], ['Tujuan Penilaian', $parts['tujuan']]] as $i => [$label, $val]) {
                $t->addRow(null, ['cantSplit' => true]);
                $pad = ['spaceAfter' => $i === 0 ? 120 : 0, 'spaceBefore' => 0];
                $lc = $t->addCell(Converter::cmToTwip(3.3) + $this->bodyIndent);
                $lc->addText($label, $this->fBody, ['indentation' => ['left' => $this->bodyIndent]] + $pad);
                $t->addCell(Converter::cmToTwip(0.35))->addText(':', $this->fBody, $pad);
                $vc = $t->addCell(Converter::cmToTwip(12.4) - $this->bodyIndent);
                $vr = $vc->addTextRun(['alignment' => Jc::BOTH] + $pad);
                foreach ($this->styleRuns($val) as [$rt, $b, $it]) {
                    $vr->addText($rt, $this->runFont($b, $it));
                }
            }
        });
    }

    private function maksudTujuanParts(): array
    {
        $p = $this->project->proposal_purpose;
        $klien = $this->project->instructingClient->client_name;

        $maksud = match ($p) {
            Project::PURPOSE_LELANG      => $this->cl['maksud_lelang'],
            Project::PURPOSE_LK_PROPERTI => $this->cl['maksud_wajar'],
            default                      => $this->cl['maksud_pasar'],
        };
        $tujuan = match ($p) {
            Project::PURPOSE_JUAL_BELI        => strtr($this->cl['tujuan_jual_beli'], [':klien' => $klien]),
            Project::PURPOSE_PENJAMINAN_UTANG => strtr($this->cl['tujuan_penjaminan'], [':klien' => $klien]),
            Project::PURPOSE_LELANG           => strtr($this->cl['tujuan_lelang'], [':klien' => $klien]),
            Project::PURPOSE_LK_PROPERTI      => strtr($this->cl['tujuan_lk'], [
                ':objek' => $this->project->asset_type ?: '…sebutkan objek penilaian…',
                ':psak'  => $this->project->psak_classification ?: 'Aset Tetap/Investasi/Persediaan/lainnya',
            ]),
        };

        return ['maksud' => $maksud, 'tujuan' => $tujuan];
    }

    private function sectionDasarNilai(): void
    {
        $this->sectionTitle('Dasar Nilai');
        $this->bodyOr('dasar_nilai', fn () => $this->dasarNilaiBaku());

        if ($this->project->proposal_purpose === Project::PURPOSE_LELANG) {
            // Khusus Lelang: Waktu Ekspos jadi sub-bab bernomor tersendiri
            // (bukan sekadar sub-judul tebal di dalam "Dasar Nilai").
            $this->sectionTitle('Waktu Ekspos (Exposure Time)');
            $this->bodyOr('waktu_ekspos', fn () => $this->para($this->cl['def_waktu_ekspos']));
        }
    }

    /**
     * Blok "Dasar Nilai" (tanpa Waktu Ekspos). Tiap elemen:
     *   ['head' => ?string sub-judul tebal, 'body' => string paragraf].
     */
    private function dasarNilaiBlocks(): array
    {
        $blocks = [
            ['head' => null, 'body' => strtr($this->cl['dasar_nilai_intro'], [':dasar' => $this->project->value_basis_label])],
        ];

        if ($this->project->primary_value_basis === 'Nilai Pasar') {
            $blocks[] = ['head' => 'NILAI PASAR (Market Value)', 'body' => $this->cl['def_nilai_pasar']];
        } else {
            $pojk = $this->project->shows_pojk28_clause ? $this->cl['pojk28_suffix'] : '';
            $blocks[] = ['head' => 'NILAI WAJAR (Fair Value)', 'body' => strtr($this->cl['def_nilai_wajar'], [':pojk' => $pojk])];
        }

        if ($this->project->proposal_purpose === Project::PURPOSE_LELANG) {
            $blocks[] = ['head' => 'NILAI LIKUIDASI (Liquidation Value)', 'body' => $this->cl['def_nilai_likuidasi']];
        } elseif ($this->project->proposal_purpose === Project::PURPOSE_PENJAMINAN_UTANG) {
            $blocks[] = ['head' => null, 'body' => $this->cl['pu_likuidasi_note']];
        }

        return $blocks;
    }

    private function dasarNilaiBaku(): void
    {
        foreach ($this->dasarNilaiBlocks() as $b) {
            if ($b['head'] !== null) {
                $ps = ['spaceAfter' => 40, 'keepNext' => true] + $this->bodyIndentStyle();
                // "NILAI PASAR " tebal + "(Market Value)" tebal-miring.
                if (preg_match('/^(.*?)(\s*\([^)]*\))\s*$/u', $b['head'], $m)) {
                    $run = $this->s->addTextRun($ps);
                    $run->addText($m[1], $this->fBold);
                    $run->addText($m[2], ['italic' => true] + $this->fBold);
                } else {
                    $this->s->addText($b['head'], $this->fBold, $ps);
                }
            }
            $this->para($b['body']);
        }
    }

    private function sectionTanggalPenilaian(): void
    {
        $this->sectionTitle('Tanggal Penilaian');
        $this->bodyOr('tanggal_penilaian', function () {
            foreach ($this->tanggalPenilaianParas() as $p) {
                $this->para($p);
            }
        });
    }

    /**
     * Format tanggal ke Bahasa Indonesia untuk dokumen ("03 Juli 2026"),
     * lepas dari APP_LOCALE (yang = 'en'). Carbon sudah mem-bundle data
     * locale 'id' — tidak perlu paket tambahan. copy() supaya instance
     * asli (hasil cast model) tidak ikut berubah locale-nya.
     */
    private function idDate(?\Carbon\Carbon $d): string
    {
        return $d ? $d->copy()->locale('id')->translatedFormat('d F Y') : '';
    }

    private function tanggalPenilaianParas(): array
    {
        if ($this->project->proposal_purpose === Project::PURPOSE_LK_PROPERTI) {
            $tgl = $this->idDate($this->project->valuation_date) ?: '…………………';

            return array_map(
                fn ($p) => strtr($p, [':tgl' => $tgl]),
                $this->cl['tanggal_penilaian_lk']
            );
        }

        $tgl = $this->project->valuation_date
            ? ' (tanggal penilaian: ' . $this->idDate($this->project->valuation_date) . ')'
            : '';

        return [strtr($this->cl['tanggal_penilaian_umum'], [
            ':tgl'   => $tgl,
            ':dasar' => $this->project->value_basis_label,
        ])];
    }

    private function sectionTKI(): void
    {
        $this->sectionTitle('Tingkat Kedalaman Investigasi');
        $this->bodyOr('tki', fn () => $this->tkiBaku());
    }

    private function tkiBaku(): void
    {
        $this->para($this->cl['tki_intro'], null, ['keepNext' => true]);

        $alasan = $this->tkiAlasanLimited();
        foreach ($this->cl['tki_items'] as $it) {
            $this->listBullet(strtr($it, [':alasan_limited' => $alasan]));
        }

        // Blok non-destruktif per jenis aset — ikut kategori objek proposal.
        foreach ($this->tkiExtraBlockKeys() as $key) {
            foreach ($this->cl[$key] as $p) {
                $this->para($p);
            }
        }
    }

    private function tkiAlasanLimited(): string
    {
        return trim((string) ($this->project->limited_inspection_reason ?? ''))
            ?: $this->cl['tki_alasan_limited_placeholder'];
    }

    private function tkiExtraBlockKeys(): array
    {
        $blocks = [];
        if ($this->hasBangunan())                                          $blocks[] = 'tki_bangunan';
        if ($this->hasCategory('Personal Properti - Mesin dan Peralatan')) $blocks[] = 'tki_mesin';
        if ($this->hasCategory('Personal Properti - Kendaraan'))           $blocks[] = 'tki_kendaraan';
        if ($this->hasCategory('Personal Properti - Alat Berat'))          $blocks[] = 'tki_alat_berat';

        return $blocks;
    }

    private function sectionSifatSumber(): void
    {
        $this->sectionTitle('Sifat dan Sumber Informasi yang Dapat Diandalkan');
        $this->bodyOr('sifat_sumber', fn () => $this->para($this->cl['sifat_sumber']));
    }

    private function sectionAsumsi(): void
    {
        $this->sectionTitle('Asumsi Umum dan Asumsi Khusus');
        $this->bodyOr('asumsi', fn () => $this->asumsiBaku());
    }

    private function asumsiBaku(): void
    {
        $this->para($this->cl['asumsi_intro'], null, ['keepNext' => true]);
        foreach ($this->cl['asumsi_items'] as $it) {
            $this->listBullet($it);
        }
        if ($this->hasBangunan()) {
            $this->listBullet($this->cl['asumsi_bangunan_item']);
        }
        foreach ($this->cl['asumsi_khusus'] as $p) {
            $this->para($p);
        }
    }

    private function sectionPublikasi(): void
    {
        $this->sectionTitle('Persyaratan atas Persetujuan untuk Publikasi');
        $this->bodyOr('publikasi', fn () => $this->para($this->cl['publikasi']));
    }

    private function sectionKonfirmasiSPI(): void
    {
        $this->sectionTitle('Konfirmasi bahwa Penilaian dilakukan Berdasarkan SPI');
        $this->bodyOr('konfirmasi_spi', fn () => $this->para(
            strtr($this->cl['konfirmasi_spi'], [':spi_code' => $this->cl['spi_code_default']])
        ));
    }

    private function sectionLaporan(): void
    {
        $this->sectionTitle('Laporan Penilaian');
        $this->bodyOr('laporan', fn () => $this->laporanBaku());
    }

    private function laporanBaku(): void
    {
        [$style, $dt, $draft, $final, $total] = $this->laporanParts();

        // Bab 14 = daftar huruf a–d (a=pengantar, b=draft, c=final+Struktur, d=rangkap).
        $this->richListItem(strtr($this->cl['laporan_intro'], [':style' => $style, ':total' => $dt($total)]));
        $this->richListItem(strtr($this->cl['laporan_draft'], [':draft' => $dt($draft)]));

        // Item c = teks final + sub-blok "Struktur Laporan Penilaian" (penanda
        // "–"), SEMUA di dalam sel isi item c supaya tak ada jarak tabel.
        $this->listNo++;
        [$mc, $cc] = $this->openListRow();
        $mc->addText($this->alphaMarker($this->listNo) . '.', $this->fBody,
            ['indentation' => ['left' => $this->bodyIndent]] + $this->listCellPara());
        $this->writeStyled($cc, strtr($this->cl['laporan_final'], [':final' => $dt($final)]));
        $this->writeStyled($cc, $this->cl['laporan_struktur_intro']);
        foreach ($this->cl['laporan_struktur'] as $it) {
            $dr = $cc->addTextRun(['alignment' => Jc::BOTH, 'indentation' => ['left' => 200]] + $this->listCellPara());
            $dr->addText("\u{2013}  ", $this->fBody);
            foreach ($this->styleRuns($it) as [$t, $b, $i2]) {
                $dr->addText($t, $this->runFont($b, $i2));
            }
        }

        $this->richListItem($this->cl['laporan_rangkap']);   // d
    }

    /** Komponen kalimat Laporan Penilaian (dipakai baku + versi plain). */
    private function laporanParts(): array
    {
        $isLong = $this->project->report_style === Project::REPORT_LONG;
        $style  = $isLong ? $this->cl['style_long'] : $this->cl['style_short'];
        $draft  = $this->project->sla_draft_days;
        $final  = $this->project->sla_final_days;
        $total  = ($draft && $final) ? ($draft + $final) : null;
        $dt = fn ($n) => $n ? $n . ' (' . Terbilang::words($n) . ')' : '-- (----)';

        return [$style, $dt, $draft, $final, $total];
    }

    private function sectionBatasanTanggungJawab(): void
    {
        $this->sectionTitle('Batasan atau Pengecualian atas Tanggung Jawab kepada Pihak selain Pemberi Tugas');
        $this->bodyOr('batasan_tanggung_jawab', fn () => $this->para($this->cl['batasan_tanggung_jawab']));
    }

    private function sectionKebenaranData(): void
    {
        $this->sectionTitle('Pernyataan Kebenaran Data dan Informasi yang Diberikan oleh Pemberi Tugas');
        $this->bodyOr('kebenaran_data', function () {
            $this->para($this->cl['kebenaran_data_intro']);
            $items = $this->cl['kebenaran_data_items'];
            $tail  = array_pop($items);          // butir terakhir (d)
            foreach ($items as $it) {            // a, b, c
                $this->listNum($it);
            }
            $this->para($tail);                  // d -> paragraf, bukan item huruf
        });
    }

    private function sectionPendekatan(): void
    {
        $this->sectionTitle('Pendekatan yang Digunakan');
        $this->bodyOr('pendekatan', function () {
            $this->para($this->cl['pendekatan_intro']);
            foreach ($this->cl['pendekatan_items'] as $it) {
                $this->listItemLead($it['lead'] . ', ', $it['text']);
            }
        });
    }

    private function sectionKondisiPembatas(): void
    {
        $this->sectionTitle('Kondisi Pembatas Penilaian');
        $this->bodyOr('kondisi_pembatas', fn () => $this->para($this->cl['kondisi_pembatas']));
    }

    /**
     * LAMPIRAN PERMINTAAN DATA - DATA — di paling akhir dokumen, setelah
     * blok tanda tangan (REV.1). BUKAN bab bernomor.
     */
    private function sectionLampiran(): void
    {
        // Judul Lampiran rata tengah tanpa nomor -> isi mulai dari margin
        // (tidak ada "N. " untuk disejajarkan).
        $this->closeList();
        $this->listJustClosed = false;
        $this->bodyIndent = 0;

        // Lampiran Permintaan Data SELALU mulai di halaman baru tersendiri
        // (halaman terakhir), terpisah dari blok tanda tangan.
        $this->s->addPageBreak();
        $this->s->addText(
            $this->cl['lampiran_title'],
            ['bold' => true, 'size' => self::HEADING_SIZE, 'name' => $this->fName],
            ['spaceBefore' => 200, 'spaceAfter' => 160, 'alignment' => Jc::CENTER, 'keepNext' => true]
        );
        $this->bodyOr('lampiran', fn () => $this->lampiranBaku());
    }

    private function lampiranBaku(): void
    {
        $this->para($this->cl['data_diperlukan_intro'], null, ['keepNext' => true]);
        foreach ($this->lampiranItems() as $it) {
            $this->listBullet($it);
        }
    }

    private function lampiranItems(): array
    {
        $items = $this->cl['data_diperlukan_items'];
        if ($this->project->proposal_purpose === Project::PURPOSE_LK_PROPERTI) {
            array_unshift($items, $this->cl['data_diperlukan_lk_extra']);
        }

        return $items;
    }

    private function sectionProsedur(): void
    {
        $this->sectionTitle('Prosedur Pelaksanaan Penugasan');
        $this->bodyOr('prosedur', function () {
            $this->para($this->cl['prosedur_intro'], null, ['keepNext' => true]);
            foreach ($this->cl['prosedur_items'] as $it) {
                $this->listNum($it);
            }
        });
    }

    private function sectionBiaya(): void
    {
        $this->sectionTitle('Biaya Jasa Penilaian');
        $p      = $this->project;
        $total  = round($p->total_fee);          // angka final (gross), rupiah bulat
        $pct    = $this->ppnPct();

        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.') . ',00';

        $ovBiaya = $this->hasOverride('biaya');

        $this->bodyOr('biaya', fn () => $this->para($this->cl['biaya_intro']));

        // Nominal biaya (+ terbilang) rata tengah.
        $this->s->addText($rp($total), $this->fBold, ['alignment' => Jc::CENTER, 'spaceAfter' => 20]);
        $this->s->addText('(' . Terbilang::make($total) . ')', $this->fBold, ['alignment' => Jc::CENTER, 'spaceAfter' => 120]);

        // Kalimat status PPN — bagian dari override 'biaya' (ikut ter-render
        // di kotak teks), jadi hanya dicetak di sini kalau TIDAK di-override.
        if (! $ovBiaya) {
            foreach ($this->biayaCaptionLines() as $line) {
                $this->para($line, ['italic' => true] + $this->fBody);
            }
        }

        $ind = $this->bodyIndentStyle();

        // Rincian Biaya (opsional) — Fee / Transport / PPN / Total.
        // Satu baris tabel (cantSplit) berisi 3 cell agar tak terbelah halaman.
        if ($p->fee_breakdown) {
            $this->s->addText($this->cl['biaya_rincian_label'], $this->fBold, ['spaceBefore' => 80, 'spaceAfter' => 40, 'keepNext' => true] + $ind);
            $rows = [['Fee', $rp($p->fee_professional), false]];
            // Baris Transport hanya kalau TA ikut ditagih — bila ditanggung
            // klien (reimburse) nilainya memang tidak masuk total.
            if (! $p->transport_reimbursed) {
                $rows[] = ['Transport', $rp($p->fee_transport_display), false];
            }
            $rows[] = ['PPN ' . $pct . '%', $rp($p->fee_ppn_amount), false];
            $rows[] = ['Total', $rp($total), true];
            $colW = $this->indentedColWidths([2.6, 0.3, 5.0]);
            $rin  = $this->s->addTable($this->indentedTableStyle(array_sum($colW)));
            $rin->addRow(null, ['cantSplit' => true]);
            $cK = $rin->addCell($colW[0]);
            $cC = $rin->addCell($colW[1]);
            $cV = $rin->addCell($colW[2]);
            foreach ($rows as [$k, $v, $bold]) {
                $f = $bold ? $this->fBold : $this->fBody;
                $cK->addText($k, $f, ['spaceAfter' => 0]);
                $cC->addText(':', $this->fBody, ['spaceAfter' => 0]);
                $cV->addText($v, $f, ['spaceAfter' => 0]);
            }
            $this->s->addTextBreak(1);
        }

        // Termin pembayaran rata kiri.
        $this->closeList();
        // keepNext: label "Termin pembayaran" tidak boleh ditinggal sendirian
        // di dasar halaman, dan seluruh item terminnya ikut pindah bersama
        // (2026-09-24, feedback user). Bloknya pendek (2-4 baris), jadi ruang
        // kosong yang mungkin tersisa paling banyak beberapa baris.
        $this->s->addText($this->cl['termin_label'], $this->fBold, ['alignment' => Jc::START, 'spaceAfter' => 20, 'keepNext' => true] + $ind);
        $this->listJustClosed = false;
        // Termin mengikuti persentase proposal (2026-09-19, feedback user):
        // DP di Awal default 50/50, Bayar Nanti default 100%, dan staf boleh
        // menimpanya. Sisa pembulatan jatuh ke termin terakhir supaya jumlah
        // seluruh termin persis sama dengan total biaya.
        $percents = $p->paymentTermPercents();
        $last     = count($percents) - 1;

        $this->keepRows(function () use ($percents, $last, $total, $rp) {
            $sisa = $total;

            foreach ($percents as $i => $pctTerm) {
                $nominal = $i === $last ? $sisa : round($total * $pctTerm / 100);
                $sisa   -= $nominal;

                // Item terakhir dilepas ikatannya supaya blok termin tidak ikut
                // menyeret blok Rekening Bank sesudahnya.
                $this->listKeepNext = $i !== $last;

                $key = $last === 0 ? 'termin_item_last' : ($i === 0 ? 'termin_item_first' : ($i === $last ? 'termin_item_last' : 'termin_item_mid'));
                $this->listNum(strtr($this->cl[$key], [
                    ':pct'        => rtrim(rtrim(number_format($pctTerm, 2, ',', '.'), '0'), ','),
                    ':pct_words'  => Terbilang::words((int) round($pctTerm)),
                    ':rp'         => $rp($nominal),
                    ':terbilang'  => Terbilang::make($nominal),
                ]), Jc::START);
            }
        });

        // Rekening Bank & NPWP dalam 2 kolom: kiri = rekening (bisa 1–2),
        // kanan = NPWP (baku, dari config). Titik-dua dirapikan via kvTable.
        $this->closeList();
        // keepNext: label menempel pada tabel rekeningnya (2026-09-24).
        $this->s->addText($this->cl['rekening_label'], $this->fBold, ['spaceAfter' => 40, 'keepNext' => true] + $ind);
        $this->listJustClosed = false;

        $colW = $this->indentedColWidths([9.5, 6.4]);
        $rt   = $this->s->addTable($this->indentedTableStyle(array_sum($colW)));
        $rt->addRow(null, ['cantSplit' => true]);

        $lc = $rt->addCell($colW[0]);
        foreach (array_values($this->bankAccounts()) as $i => $b) {
            if ($i > 0) {
                $lc->addTextBreak(1);
            }
            $rows = [['Bank', $b['bank_name'] ?? '-']];
            if (! empty($b['branch'])) {
                $rows[] = ['Cabang', $b['branch']];
            }
            $rows[] = ['Atas Nama', $b['account_name'] ?? '-'];
            $rows[] = ['No. Rek', $b['account_number'] ?? '-'];
            $this->kvTable($lc, $rows, 2.4, 6.4, true);
        }

        $rc = $rt->addCell($colW[1]);
        $this->kvTable($rc, [['NPWP No.', $this->cfg['npwp']]], 2.2, 3.6, true);

        $this->s->addTextBreak(1);
        // Kalimat pembatalan tebal + miring (contoh proposal resmi 02309).
        $this->para($this->cl['biaya_pembatalan'], ['bold' => true, 'italic' => true] + $this->fBody);
    }

    /** Persentase PPN diformat "11" / "11,5" (tanpa nol berlebih). */
    private function ppnPct(): string
    {
        return rtrim(rtrim(number_format($this->project->fee_ppn_rate * 100, 2, ',', ''), '0'), ',');
    }

    private function sectionPernyataanPemberiTugas(): void
    {
        $this->sectionTitle('Pernyataan Pemberi Tugas');
        // Bab 25 + blok tanda tangan diikat dengan keepNext supaya blok
        // tanda tangan tidak berdiri sendiri di halaman terakhir isi —
        // minimal bab 25 ikut di halaman yang sama (bab 24 ikut kalau muat).
        $this->bodyOr('pernyataan_pemberi_tugas', function () {
            $this->para($this->cl['pernyataan_pemberi_tugas'], null, ['keepNext' => true]);
            $this->para($this->cl['penutup_spk'], null, ['keepNext' => true]);
        }, null, ['keepNext' => true]);
    }

    /** File gambar sementara (gabungan barcode + stempel), dihapus setelah save(). */
    private array $tempFiles = [];

    // Sisi barcode di dokumen (cm), sama dengan Surat Tugas.
    private const BARCODE_CM = 2.65;

    /**
     * Gambar untuk blok "Hormat kami": [path, lebar cm, tinggi cm] atau null.
     * Barcode + stempel digabung jadi SATU gambar PNG lewat GD, supaya posisi
     * stempel yang menimpa barcode sama persis di Word maupun LibreOffice
     * (gambar mengambang sering bergeser antarversi LibreOffice).
     */
    private function signatureImage(): ?array
    {
        $p       = $this->project;
        $disk    = \Illuminate\Support\Facades\Storage::disk('public');
        $barcode = $p->use_signature_barcode && $p->signature_barcode && $disk->exists($p->signature_barcode)
            ? $disk->path($p->signature_barcode) : null;
        $stamp   = $p->use_stamp && is_file(public_path('images/stempel-spr.png'))
            ? public_path('images/stempel-spr.png') : null;

        if (! $barcode && ! $stamp) {
            return null;
        }
        if (! $stamp) {
            $bc = $this->cleanBarcode($barcode);
            if (! $bc) {
                return [$barcode, self::BARCODE_CM, self::BARCODE_CM];
            }
            return [$this->tempPng($bc), self::BARCODE_CM, self::BARCODE_CM];
        }

        $st = imagecreatefrompng($stamp);
        $sw = imagesx($st);
        $sh = imagesy($st);

        if (! $barcode) {
            imagedestroy($st);
            $w = 4.5;
            return [$stamp, $w, $w * $sh / $sw];
        }

        // Kanvas: barcode di kiri, stempel menimpa dari tengah barcode ke
        // kanan (meniru contoh dokumen resmi). Satuan piksel barcode (370).
        $bc  = $this->cleanBarcode($barcode);
        $q   = 370;
        $cw  = (int) round($q * 2.3);
        $img = imagecreatetruecolor($cw, $q);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        if ($bc) {
            imagecopyresampled($img, $bc, 0, 0, 0, 0, $q, $q, imagesx($bc), imagesy($bc));
            imagedestroy($bc);
        }
        $tw = (int) round($q * 1.8);
        $th = (int) round($tw * $sh / $sw);
        imagealphablending($img, true);
        imagecopyresampled($img, $st, (int) round($q * 0.45), (int) round(($q - $th) / 2 - $q * 0.08), 0, 0, $tw, $th, $sw, $sh);
        imagedestroy($st);

        return [$this->tempPng($img), self::BARCODE_CM * $cw / $q, self::BARCODE_CM];
    }

    /**
     * Barcode dengan latar putih dibuat transparan dan tepi 4 piksel
     * dibuang (2026-09-21, feedback user): garis abu-abu di tepi file
     * barcode tidak lagi terlihat sebagai bingkai kaku di dokumen.
     */
    private function cleanBarcode(string $path): ?\GdImage
    {
        $src = @imagecreatefromstring((string) file_get_contents($path));
        if (! $src) {
            return null;
        }
        $w   = imagesx($src);
        $h   = imagesy($src);
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $clear = imagecolorallocatealpha($img, 255, 255, 255, 127);
        $edge  = 4;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($src, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $onEdge = $x < $edge || $y < $edge || $x >= $w - $edge || $y >= $h - $edge;
                imagesetpixel($img, $x, $y, ($onEdge || min($r, $g, $b) > 200) ? $clear : $rgb & 0xFFFFFF);
            }
        }
        imagedestroy($src);

        return $img;
    }

    /** Simpan gambar GD ke PNG sementara (dihapus setelah save()). */
    private function tempPng(\GdImage $img): string
    {
        $out = storage_path('app/tmp/ttd-' . uniqid() . '.png');
        @mkdir(dirname($out), 0775, true);
        imagepng($img, $out);
        imagedestroy($img);
        $this->tempFiles[] = $out;

        return $out;
    }

    private function sectionTandaTangan(): void
    {
        // Paragraf jeda ber-keepNext = jembatan supaya blok tanda tangan
        // (tabel di bawah) menempel dengan Bab 25.
        $this->s->addText('', $this->fBody, ['spaceBefore' => 480, 'spaceAfter' => 0, 'keepNext' => true]);

        $sig      = $this->signatory();
        $approver = $this->project->effective_approver_name;

        // cantSplit: blok tanda tangan tidak boleh terbelah dua halaman.
        // Digeser sejajar bodyIndent (sama seperti paragraf Bab 25 di
        // atasnya) via indentedColWidths()/indentedTableStyle().
        $colW = $this->indentedColWidths([9.9, 6.0]);
        $t    = $this->s->addTable($this->indentedTableStyle(array_sum($colW)));
        $t->addRow(null, ['cantSplit' => true]);

        // Kolom kiri (blok KJPP/penandatangan) sengaja dilebarkan supaya
        // kolom kanan ("Menyetujui," dst) bergeser ke kanan & tidak
        // menempel dengan blok tanda tangan Penilai.
        $l = $t->addCell($colW[0]);
        $l->addText('Hormat kami,', $this->fBody, ['spaceAfter' => 0]);
        $l->addText(strtoupper($this->cfg['company_name']), $this->fBold, ['spaceAfter' => 0]);
        $l->addText($this->cfg['company_tagline'], ['italic' => true] + $this->fBody, ['spaceAfter' => 0]);
        // Barcode tanda tangan / stempel (2026-09-21). Tanpa keduanya, ruang
        // tanda tangan basah seperti semula (feedback user 2026-09-21).
        $img = $this->signatureImage();
        if (! $img) {
            $l->addTextBreak(7);
        } else {
            [$file, $wCm, $hCm] = $img;
            $l->addImage($file, [
                'width'  => Converter::cmToPoint($wCm),
                'height' => Converter::cmToPoint($hCm),
                'alignment' => Jc::START,
            ]);
        }
        $l->addText($sig['name'], $this->fBold, ['spaceAfter' => 0]);
        $l->addText($sig['title'], ['italic' => true] + $this->fBody, ['spaceAfter' => 0]);
        $l->addText('Penilai Properti Izin Menkeu No. : ' . $sig['izin_pp_no'], $this->fBody, ['spaceAfter' => 0]);
        $l->addText('MAPPI No. ' . $sig['mappi_no'], $this->fBody, ['spaceAfter' => 0]);
        $l->addText($sig['rmk_no'], $this->fBody, ['spaceAfter' => 0]);
        $l->addText('Surat Tanda Terdaftar OJK No. ' . $sig['sttd_ojk_no'], $this->fBody, ['spaceAfter' => 0]);
        $l->addText($sig['klasifikasi'], $this->fBody, ['spaceAfter' => 0]);

        $r = $t->addCell($colW[1]);
        $r->addText('Menyetujui,', $this->fBody, ['spaceAfter' => 0]);
        $r->addText($approver, $this->fBold, ['spaceAfter' => 0]);
        $r->addTextBreak(7); // ruang tanda tangan + stempel
        // Garis dipendekkan (2026-09-14, feedback user) — versi lama lebih
        // lebar dari kolom sehingga ")" turun ke baris kedua.
        $r->addText('( ________________________ )', $this->fBody, ['spaceAfter' => 0]);
        $r->addText('Jabatan:', $this->fBody, ['spaceAfter' => 0]);
        $r->addText('Tanggal:', $this->fBody, ['spaceAfter' => 0]);
    }

    // ---------- override teks per-bab (Batch 3) ----------

    private function hasOverride(string $key): bool
    {
        return isset($this->overrides[$key]) && trim($this->overrides[$key]) !== '';
    }

    /**
     * Render isi sebuah bab. Kalau ada override manual untuk $key, teks itu
     * dipakai (tabel & elemen struktural bab tetap dirender oleh pemanggil
     * di luar sini). Kalau tidak ada override, $baku() dijalankan (render
     * baku "rich" seperti biasa: daftar huruf, sub-judul tebal, dll).
     *
     * Format teks override:
     *  - BARIS KOSONG memisahkan blok. Di dalam satu blok, baris-baris yang
     *    BUKAN item daftar digabung jadi satu paragraf (aman untuk teks yang
     *    ke-wrap keras saat diketik/paste).
     *  - Baris berawalan penanda daftar (`a.` / `b)` / `1.` …) menjadi item
     *    daftar rapi (hanging indent + tab) dan dinomori ulang a, b, c per bab.
     *
     * @param array|null $firstPs Gaya paragraf PERTAMA (mis. ['keepNext' => true]).
     * @param array|null $everyPs Gaya SEMUA paragraf override (mis. keepNext
     *                            untuk bab yang harus menempel blok berikutnya).
     */
    private function bodyOr(string $key, callable $baku, ?array $firstPs = null, ?array $everyPs = null): void
    {
        if (! $this->hasOverride($key)) {
            $baku();
            return;
        }

        // Heading (sectionTitle) sudah punya keepNext sendiri — bersihkan flag
        // supaya paragraf override tidak tanpa sengaja kena keepLines beruntun.
        $this->keepWithHeading = false;
        $this->closeList();
        $this->listJustClosed = false;

        $text   = str_replace(["\r\n", "\r"], "\n", trim($this->overrides[$key]));
        $blocks = preg_split('/\n{2,}/', $text) ?: [$text];

        $first    = true;
        $prevList = false;
        $buf      = [];

        $flush = function () use (&$buf, &$first, &$prevList, $firstPs, $everyPs): void {
            $p = trim(implode(' ', $buf));
            $buf = [];
            if ($p === '') {
                return;
            }
            $this->para($p, null, $everyPs ?? ($first ? $firstPs : null));
            $first = false;
            $prevList = false;
        };

        foreach ($blocks as $block) {
            foreach (explode("\n", $block) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                if (preg_match('/^(?:[a-z]{1,2}|\d{1,3})[.)]\s+(.+)$/su', $line, $m)) {
                    $flush();
                    if (! $prevList) {
                        $this->listNo = 0;   // daftar baru mulai dari 'a'
                    }
                    $this->listItem($m[1]);
                    $first = false;
                    $prevList = true;
                } elseif (preg_match('/^[-\x{2013}\x{2014}\x{2022}*]\s+(.+)$/su', $line, $m)) {
                    // Penanda "–"/"-"/"•"/"*" -> item dash, tetap 1 baris.
                    $flush();
                    $this->dashItem($m[1]);
                    $first = false;
                    $prevList = false;
                } else {
                    $buf[] = $line;
                }
            }
            $flush();
        }
    }

    /**
     * Daftar bab untuk halaman "Editor Teks Proposal per-Bab". Tiap entri:
     *   key, title, editable, note, overridden (bool), baku (teks baku
     *   ter-render), text (teks efektif = override kalau ada, else baku).
     */
    public function sectionsForEditor(): array
    {
        $purpose = $this->project->proposal_purpose;
        $out = [];

        foreach (self::SECTION_META as $key => $meta) {
            if (isset($meta['only']) && $meta['only'] !== $purpose) {
                continue;
            }

            $baku = $meta['editable'] ? $this->bakuText($key) : '';
            $ov   = $this->hasOverride($key) ? $this->overrides[$key] : null;

            $out[] = [
                'key'        => $key,
                'title'      => $meta['title'],
                'editable'   => $meta['editable'],
                'note'       => $meta['note'],
                'overridden' => $ov !== null,
                'baku'       => $baku,
                'text'       => $ov ?? $baku,
            ];
        }

        return $out;
    }

    /** Key bab yang boleh menerima override (untuk validasi controller). */
    public static function editorSectionKeys(): array
    {
        return array_keys(array_filter(
            self::SECTION_META,
            fn ($m) => $m['editable']
        ));
    }

    /**
     * Teks BAKU sebuah bab, sudah ter-render (placeholder diganti nilai
     * proyek), dalam bentuk plain — paragraf dipisah baris kosong, item
     * daftar diberi penanda huruf literal (a.   b.   …). Dipakai untuk
     * mengisi textarea di editor & sebagai target "Reset ke baku".
     *
     * CATATAN: jaga tetap sinkron dengan render "rich" di section*() /
     * *Baku() masing-masing.
     */
    private function bakuText(string $key): string
    {
        return match ($key) {
            'pembuka'        => $this->pembukaBaku(),
            'status_penilai' => $this->stripMd(implode("\n\n", array_map(
                fn ($k, $p) => $k === 'ojk'
                    ? $p . "\n" . implode("\n", array_map(fn ($i, $x) => ($i + 1) . '. ' . $x, array_keys($this->ojkSectors()), $this->ojkSectors()))
                    : $p,
                array_keys($this->statusPenilaiPoin()), $this->statusPenilaiPoin()
            ))),
            'pengguna_laporan_lk' => $this->pgnLaporanLkBaku(),
            'objek' => strtr($this->cl['post_objek_hubungan'], $this->objekPenutupRepl())
                . "\n\n" . $this->cl['post_objek'],
            'mata_uang' => $this->cl['mata_uang'],
            'maksud_tujuan' => 'Maksud Penilaian: ' . $this->maksudTujuanParts()['maksud']
                . "\n\nTujuan Penilaian: " . $this->maksudTujuanParts()['tujuan'],
            'dasar_nilai'  => $this->dasarNilaiPlain(),
            'waktu_ekspos' => $this->cl['def_waktu_ekspos'],
            'tanggal_penilaian' => implode("\n\n", $this->tanggalPenilaianParas()),
            'tki'          => $this->tkiPlain(),
            'sifat_sumber' => $this->cl['sifat_sumber'],
            'asumsi'       => $this->asumsiPlain(),
            'publikasi'    => $this->cl['publikasi'],
            'konfirmasi_spi' => strtr($this->cl['konfirmasi_spi'], [':spi_code' => $this->cl['spi_code_default']]),
            'laporan'      => $this->laporanPlain(),
            'batasan_tanggung_jawab' => $this->cl['batasan_tanggung_jawab'],
            'kebenaran_data' => $this->kebenaranDataPlain(),
            'pendekatan' => $this->cl['pendekatan_intro'] . "\n\n" . $this->numberedPlain(array_map(
                fn ($it) => $it['lead'] . ', ' . $it['text'],
                $this->cl['pendekatan_items']
            )),
            'kondisi_pembatas' => $this->cl['kondisi_pembatas'],
            'prosedur' => $this->cl['prosedur_intro'] . "\n\n" . $this->numberedPlain($this->cl['prosedur_items']),
            'pembatalan'   => $this->cl['pembatalan'],
            'kerahasiaan'  => $this->cl['kerahasiaan'],
            'pendamping'   => $this->cl['pendamping'],
            'berita_acara' => $this->cl['berita_acara'],
            'biaya'        => $this->biayaPlain(),
            'pernyataan_pemberi_tugas' => $this->cl['pernyataan_pemberi_tugas'] . "\n\n" . $this->cl['penutup_spk'],
            'lampiran' => $this->cl['data_diperlukan_intro'] . "\n\n" . $this->numberedPlain($this->lampiranItems()),
            default => '',
        };
    }

    private function dasarNilaiPlain(): string
    {
        $parts = [];
        foreach ($this->dasarNilaiBlocks() as $b) {
            if ($b['head'] !== null) {
                $parts[] = $b['head'];
            }
            $parts[] = $b['body'];
        }

        return implode("\n\n", $parts);
    }

    private function tkiPlain(): string
    {
        $alasan = $this->tkiAlasanLimited();
        $parts = [
            $this->cl['tki_intro'],
            $this->numberedPlain(array_map(
                fn ($it) => strtr($it, [':alasan_limited' => $alasan]),
                $this->cl['tki_items']
            )),
        ];
        foreach ($this->tkiExtraBlockKeys() as $key) {
            $parts[] = implode("\n\n", $this->cl[$key]);
        }

        return implode("\n\n", $parts);
    }

    private function asumsiPlain(): string
    {
        $items = $this->cl['asumsi_items'];
        if ($this->hasBangunan()) {
            $items[] = $this->cl['asumsi_bangunan_item'];
        }

        return implode("\n\n", array_merge(
            [$this->cl['asumsi_intro'], $this->numberedPlain($items)],
            $this->cl['asumsi_khusus'],
        ));
    }

    private function laporanPlain(): string
    {
        [$style, $dt, $draft, $final, $total] = $this->laporanParts();

        $items = $this->numberedPlain([
            strtr($this->cl['laporan_intro'], [':style' => $style, ':total' => $dt($total)]),
            strtr($this->cl['laporan_draft'], [':draft' => $dt($draft)]),
            strtr($this->cl['laporan_final'], [':final' => $dt($final)]),
            $this->cl['laporan_rangkap'],
        ]);

        $struktur = $this->cl['laporan_struktur_intro'] . "\n"
            . implode("\n", array_map(fn ($s) => '- ' . $s, $this->cl['laporan_struktur']));

        return $this->stripMd($items . "\n\n" . $struktur);
    }

    private function kebenaranDataPlain(): string
    {
        $items = $this->cl['kebenaran_data_items'];
        $tail  = array_pop($items);

        return $this->cl['kebenaran_data_intro'] . "\n\n"
            . $this->numberedPlain($items) . "\n\n" . $tail;
    }

    private function biayaPlain(): string
    {
        return $this->cl['biaya_intro'] . "\n\n" . implode("\n\n", $this->biayaCaptionLines());
    }

    /**
     * Kalimat di bawah nominal biaya (2026-09-15, keputusan user). Nominal yang
     * dicetak adalah total_fee (gross), jadi SELALU sudah termasuk PPN — status
     * "belum termasuk PPN" pada form hanya menentukan cara input dihitung dan
     * rincian untuk invoice, tidak mengubah kalimat ini. Pembedanya hanya
     * Transport & Akomodasi: ikut ditagih, atau ditanggung klien.
     * Dipakai render .docx maupun teks default editor per-bab.
     */
    private function biayaCaptionLines(): array
    {
        return [
            $this->project->transport_reimbursed
                ? $this->cl['biaya_transport_excluded']
                : $this->cl['biaya_ppn_included'],
        ];
    }

    // ---------- helpers ----------

    /** Emit heading bernomor (font lebih besar dari isi), kembalikan null. */
    private function sectionTitle(string $title): ?array
    {
        $this->closeList();
        $this->listJustClosed = false;
        $this->secNo++;
        $hFont = ['bold' => true, 'size' => self::HEADING_SIZE, 'name' => $this->fName];
        $hPara = ['spaceBefore' => 220, 'spaceAfter' => 70, 'keepNext' => true, 'keepLines' => true];

        // Istilah Inggris pada judul (mis. "(Exposure Time)") tetap dimiringkan.
        // Judul huruf besar + garis bawah (2026-09-22, feedback user); nomor
        // bab tidak ikut digarisbawahi.
        // Judul sangat panjang (mis. bab "Batasan atau Pengecualian ...")
        // dirapatkan jarak hurufnya 0,5 pt supaya tetap 1 baris.
        if (mb_strlen($title) > 70) {
            $hFont['spacing'] = -10;   // 1/20 pt
        }
        $tr = $this->s->addTextRun($hPara);
        $tr->addText($this->secNo . '. ', $hFont);
        foreach ($this->scanTerms(mb_strtoupper($title), true) as [$t, $b, $it]) {
            $tr->addText($t, ($it ? ['italic' => true] : []) + ['underline' => 'single'] + $hFont);
        }

        $this->keepWithHeading = true;
        $this->listNo = 0;   // penomoran daftar mulai dari 1 lagi di bab ini
        $this->bodyIndent = $this->numPrefixIndent();
        return null;
    }

    /** Perkiraan lebar "N. " pada font isi (twip) — utk indent isi bab. */
    private function numPrefixIndent(): int
    {
        return (int) Converter::cmToTwip(0.20 + 0.20 * strlen((string) $this->secNo));
    }

    /** Gaya indent isi bab (kosong bila di luar bab). */
    private function bodyIndentStyle(): array
    {
        return $this->bodyIndent > 0 ? ['indentation' => ['left' => $this->bodyIndent]] : [];
    }

    // ---------- deteksi kategori objek (untuk klausul kondisional) ----------

    private function assetCategories(): array
    {
        return $this->project->valuationObjects->pluck('asset_category')->filter()->unique()->all();
    }

    private function hasBangunan(): bool
    {
        foreach ($this->assetCategories() as $c) {
            if (str_contains($c, 'Bangunan')) {
                return true;
            }
        }
        return false;
    }

    private function hasCategory(string $needle): bool
    {
        return in_array($needle, $this->assetCategories(), true);
    }

    /**
     * Emit satu paragraf isi bab. Teks otomatis diberi gaya:
     *  - markup **…** -> tebal
     *  - istilah Inggris (config text_style.italic) -> miring
     *  - sumber/standar (config text_style.bold) -> tebal
     */
    private function para(string $text, ?array $font = null, ?array $pExtra = null): void
    {
        $this->closeList();
        $this->listJustClosed = false;

        $pStyle = $this->pJustify + $this->bodyIndentStyle();
        if ($this->keepWithHeading) {
            $pStyle['keepLines'] = true;   // heading + paragraf ini tidak terpisah halaman
            $this->keepWithHeading = false;
        }
        if ($pExtra) {
            $pStyle = $pExtra + $pStyle;   // override (mis. 'keepNext' => true)
        }

        $runs = $this->styleRuns($text);
        if ($font === null && count($runs) === 1 && ! $runs[0][1] && ! $runs[0][2]) {
            $this->s->addText($runs[0][0], $this->fBody, $pStyle);   // jalur cepat: polos
            return;
        }
        $tr = $this->s->addTextRun($pStyle);
        foreach ($runs as [$t, $b, $it]) {
            $tr->addText($t, $this->runFont($b, $it, $font));
        }
    }

    // Alias historis — para() kini sudah menangani markup + gaya otomatis.
    private function richPara(string $text, ?array $pExtra = null): void
    {
        $this->para($text, null, $pExtra);
    }

    /** Buang markup **…** (untuk textarea editor teks). */
    /**
     * Teks baku untuk editor. Penanda **tebal** sekarang DIBIARKAN (2026-09-22,
     * feedback user) supaya bagian tebal terlihat & bisa ditambah/dihapus di
     * Editor Teks; saat dicetak, **...** jadi huruf tebal.
     */
    private function stripMd(string $text): string
    {
        return $text;
    }

    private function runFont(bool $bold, bool $italic, ?array $base = null): array
    {
        $f = $base ?? $this->fBody;
        if ($bold) {
            $f['bold'] = true;
        }
        if ($italic) {
            $f['italic'] = true;
        }
        return $f;
    }

    /**
     * Pecah teks jadi run bergaya: [[teks, bool tebal, bool miring], …].
     * Markup **…** = tebal eksplisit; di dalam tiap segmen, istilah Inggris
     * jadi miring & nama sumber (SPI/KEPI/…) jadi tebal.
     */
    private function styleRuns(string $text, bool $forceBold = false): array
    {
        $out = [];
        foreach (explode('**', $text) as $i => $seg) {
            if ($seg === '') {
                continue;
            }
            $baseBold = $forceBold || (bool) ($i % 2);
            foreach ($this->scanTerms($seg, $baseBold) as $r) {
                $out[] = $r;
            }
        }
        return $out ?: [[$text, $forceBold, false]];
    }

    // Kutipan sumber ber-tanda kurung: "(SPI 106 3.12 - …)", "(KEPI 5.8 C.4)",
    // "(Interpretasi SPI 102 …)" -> SELURUH kurung sampai penutup di-tebalkan.
    // Harus diikuti nomor (mis. "SPI 101.3.1"), jadi singkatan saja seperti
    // "(KEPI)" / "(SPI)" tidak ikut tebal (2026-09-22, contoh proposal 02309).
    private const CITATION_RE = '/\([^()]*(?:SPI|KEPI|PSAK|POJK|PMK)\s+\d[^()]*\)/u';

    /** Cari kutipan sumber (tebal penuh), sumber (tebal), istilah (miring). */
    private function scanTerms(string $seg, bool $baseBold): array
    {
        $marks = [];   // [offset, length, bold, italic] — kutipan ditambahkan
                       // paling awal supaya menang saat tumpang tindih.
        if (preg_match_all(self::CITATION_RE, $seg, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm[0] as [$s, $off]) {
                $marks[] = [$off, strlen($s), true, false];
            }
        }
        if ($this->italicRe !== '' && preg_match_all($this->italicRe, $seg, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm[0] as [$s, $off]) {
                $marks[] = [$off, strlen($s), $baseBold, true];
            }
        }
        if ($this->sourceRe !== '' && preg_match_all($this->sourceRe, $seg, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm[0] as [$s, $off]) {
                $marks[] = [$off, strlen($s), true, false];
            }
        }
        if (! $marks) {
            return [[$seg, $baseBold, false]];
        }

        usort($marks, fn ($a, $b) => $a[0] <=> $b[0]);
        $out = [];
        $pos = 0;
        foreach ($marks as [$off, $len, $b, $it]) {
            if ($off < $pos) {
                continue;   // tumpang tindih -> yang pertama menang
            }
            if ($off > $pos) {
                $out[] = [substr($seg, $pos, $off - $pos), $baseBold, false];
            }
            $out[] = [substr($seg, $off, $len), $b, $it];
            $pos = $off + $len;
        }
        if ($pos < strlen($seg)) {
            $out[] = [substr($seg, $pos), $baseBold, false];
        }
        return $out;
    }

    /** 1->a, 2->b, … 26->z, 27->aa, … (penanda daftar). */
    private function alphaMarker(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $n--;
            $s = chr(97 + $n % 26) . $s;
            $n = intdiv($n, 26);
        }
        return $s;
    }

    /**
     * Gabung daftar item jadi satu string plain dengan penanda huruf
     * (a. b. …), satu item per baris — untuk textarea editor.
     */
    private function numberedPlain(array $items): string
    {
        $out = [];
        foreach (array_values($items) as $i => $it) {
            $out[] = $this->alphaMarker($i + 1) . '. ' . $it;
        }
        return implode("\n", $out);
    }

    // Ukuran font (pt) judul sub-bab bernomor + judul "LAMPIRAN …".
    // Isi paragraf memakai config('kjpp.pdf_font_size') (default 11).
    // 11 pt sejak 2026-09-22 (huruf besar membuat judul panjang jadi 2 baris di 12 pt).
    private const HEADING_SIZE = 11;

    // Lebar area isi halaman (cm) = lebar A4 (21) dikurangi margin kiri &
    // kanan (2,54 masing-masing, lihat addSection() di boot()). Dipakai
    // tabel bab 4 (Identifikasi Obyek) supaya lebarnya konsisten dengan
    // lebar paragraf/margin halaman, bukan angka tebakan.
    private const PAGE_CONTENT_W_CM = 21.0 - 2.54 - 2.54;

    // Daftar bernomor dirender sebagai TABEL 2-kolom tanpa garis: kolom
    // penanda (lebar tetap) | kolom isi. Dengan begitu SEMUA penanda lurus,
    // SEMUA huruf pertama isi lurus, dan baris lanjutan otomatis sejajar isi
    // (wrap di dalam sel) — tak bergantung pada lebar penanda / perilaku tab.
    private const LIST_MARKER_COL = 210;                     // kolom penanda (dikompensasi thd cell margin bawaan)
    private const LIST_TABLE_W    = 8100;                    // ~16 cm lebar total

    /** true tepat setelah closeList() sampai ada paragraf/tabel lain. */
    private bool $listJustClosed = false;

    /** true selama emit blok daftar yang harus utuh satu halaman. */
    private bool $listKeepNext = false;

    /** Tutup tabel daftar yang sedang aktif (dipanggil sebelum emit non-daftar). */
    private function closeList(): void
    {
        if ($this->listTbl !== null) {
            $this->listTbl = null;
            $this->listKey = null;
            $this->listJustClosed = true;
        }
    }

    /**
     * Mulai / lanjutkan tabel daftar; kembalikan [selPenanda, selIsi].
     * $extra = indent tambahan (untuk sub-daftar, mis. Struktur Laporan).
     */
    private function openListRow(int $extra = 0, bool $withContent = true): array
    {
        if ($this->listTbl !== null && $this->listKey !== $extra) {
            $this->closeList();
        }
        if ($this->listTbl === null) {
            if ($this->listJustClosed) {
                // OOXML: dua tabel tak boleh berdempet -> sisipkan paragraf mini.
                $this->s->addText('', ['size' => 1], ['spaceAfter' => 0, 'spaceBefore' => 0]);
            }
            $this->listTbl = $this->s->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
            $this->listKey = $extra;
            $this->listJustClosed = false;
        }
        $this->listTbl->addRow(null, ['cantSplit' => true]);
        $markerW = $this->bodyIndent + $extra + self::LIST_MARKER_COL;
        $mc = $this->listTbl->addCell($markerW);
        if (! $withContent) {
            return [$mc, null];   // pemanggil menambah sel sendiri (numberedSubItem)
        }
        $cc = $this->listTbl->addCell(self::LIST_TABLE_W - $markerW);
        return [$mc, $cc];
    }

    private function listCellPara(): array
    {
        // keepNext aktif = baris daftar ini diikat ke baris berikutnya, jadi
        // satu blok daftar pendek tidak terbelah dua halaman (2026-09-24,
        // feedback user). Hanya dipakai untuk blok pendek — lihat keepRows().
        return ['spaceAfter' => 0, 'spaceBefore' => 0]
            + ($this->listKeepNext ? ['keepNext' => true] : []);
    }

    /**
     * Jalankan $emit dengan seluruh baris daftar terikat jadi satu blok.
     * Baris TERAKHIR sengaja dilepas ikatannya supaya blok ini tidak ikut
     * menyeret paragraf sesudahnya. Pakai hanya untuk blok pendek (kira-kira
     * maksimal sepertiga halaman); blok panjang justru menyisakan halaman
     * kosong kalau diikat.
     */
    private function keepRows(callable $emit): void
    {
        $this->listKeepNext = true;
        try {
            $emit();
        } finally {
            $this->listKeepNext = false;
        }
    }

    /** Tulis run bergaya (markup + istilah) ke sebuah kontainer. */
    private function writeStyled($container, string $text, ?string $align = null, bool $forceBold = false): void
    {
        $run = $container->addTextRun(['alignment' => $align ?? Jc::BOTH] + $this->listCellPara());
        foreach ($this->styleRuns($text, $forceBold) as [$t, $b, $it]) {
            $run->addText($t, $this->runFont($b, $it));
        }
    }

    /** Item daftar HURUF (a, b, c, …). Penomoran manual, reset tiap bab. */
    private function listItem(string $text, ?string $align = null): void
    {
        $this->listNo++;
        [$mc, $cc] = $this->openListRow();
        $mc->addText($this->alphaMarker($this->listNo) . '.', $this->fBody,
            ['indentation' => ['left' => $this->bodyIndent]] + $this->listCellPara());
        $this->writeStyled($cc, $text, $align);
    }

    // Alias historis — listItem() menangani markup + gaya otomatis.
    private function richListItem(string $text, ?string $align = null): void
    {
        $this->listItem($text, $align);
    }

    /** Poin "•" (bab Penjelasan Status Penilai, 2026-09-22). */
    private function bulletItem(string $text): void
    {
        [$mc, $cc] = $this->openListRow();
        $mc->addText("\u{2022}", $this->fBody,
            ['indentation' => ['left' => $this->bodyIndent]] + $this->listCellPara());
        $this->writeStyled($cc, $text);
    }

    /**
     * Sub-daftar "1." di bawah poin "•" (sektor OJK). Ditaruh di tabel daftar
     * yang SAMA (sel penanda kosong) supaya tidak ada paragraf pemisah antar
     * tabel yang membuat jarak kosong; nomor + teks memakai tab & hanging
     * indent sehingga baris lanjutan sejajar teks (2026-09-22).
     */
    private function numberedSubItem(int $no, string $text): void
    {
        // Baris 3 kolom di tabel poin yang sama: sel penanda "•" kosong |
        // nomor | teks. Kolom teks sendiri membuat baris lanjutan sejajar.
        [$mc] = $this->openListRow(0, false);
        $mc->addText('', $this->fBody, $this->listCellPara());
        $markerW = $this->bodyIndent + self::LIST_MARKER_COL;
        // Nomor menjorok ~0,35 cm dari awal teks poin, jarak nomor-teks rapat
        // (2026-09-22, feedback user).
        $shift   = 200;
        $numW    = $shift + 260;
        $this->listTbl->addCell($numW)->addText($no . '.', $this->fBody,
            ['indentation' => ['left' => $shift]] + $this->listCellPara());
        $this->writeStyled($this->listTbl->addCell(self::LIST_TABLE_W - $markerW - $numW), $text);
    }

    /** Item daftar penanda "–" (sub-daftar Struktur Laporan). */
    private function dashItem(string $text, int $extraIndent = 0): void
    {
        [$mc, $cc] = $this->openListRow($extraIndent);
        $mc->addText("\u{2013}", $this->fBody,
            ['indentation' => ['left' => $this->bodyIndent + $extraIndent]] + $this->listCellPara());
        $this->writeStyled($cc, $text);
    }

    /**
     * Item daftar huruf dg lead TEBAL (+ gaya otomatis) lalu sisa biasa.
     * Dipakai bab "Pendekatan yang Digunakan".
     */
    private function listItemLead(string $lead, string $rest, ?string $align = null): void
    {
        $this->listNo++;
        [$mc, $cc] = $this->openListRow();
        $mc->addText($this->alphaMarker($this->listNo) . '.', $this->fBody,
            ['indentation' => ['left' => $this->bodyIndent]] + $this->listCellPara());
        $run = $cc->addTextRun(['alignment' => $align ?? Jc::BOTH] + $this->listCellPara());
        foreach ($this->styleRuns($lead, true) as [$t, $b, $it]) {
            $run->addText($t, $this->runFont($b, $it));
        }
        foreach ($this->styleRuns($rest) as [$t, $b, $it]) {
            $run->addText($t, $this->runFont($b, $it));
        }
    }

    // Alias supaya call-site lama tetap jalan — semuanya daftar huruf.
    private function listNum(string $text, ?string $align = null): void
    {
        $this->listItem($text, $align);
    }

    private function listBullet(string $text): void
    {
        $this->listItem($text);
    }

    /**
     * Tabel mini "label : value" (kolom titik-dua sejajar) di dalam sebuah
     * cell — dipakai blok Rekening Bank / NPWP.
     */
    private function kvTable($container, array $rows, float $labelCm = 2.4, float $valCm = 6.4, bool $bold = false): void
    {
        $f = $bold ? $this->fBold : $this->fBody;
        $t = $container->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
        foreach ($rows as [$k, $v]) {
            $t->addRow();
            $t->addCell(Converter::cmToTwip($labelCm))->addText($k, $f, ['spaceAfter' => 0]);
            $t->addCell(Converter::cmToTwip(0.3))->addText(':', $f, ['spaceAfter' => 0]);
            $t->addCell(Converter::cmToTwip($valCm))->addText((string) $v, $f, ['spaceAfter' => 0]);
        }
    }

    /**
     * Rekening bank untuk blok "Rekening Bank" pada Biaya Jasa Penilaian.
     * Prioritas: rekening yang dipilih di proposal (projects.bank_id) ->
     * bank ber-is_default -> config('kjpp.bank_account') (fallback data lama).
     */
    private function bankAccounts(): array
    {
        $bank = $this->project->effectiveBank();
        if ($bank) {
            return [$bank->toClauseArray()];
        }
        return [$this->cfg['bank_account']];
    }

    // Panjang maksimum satu baris alamat (kira-kira 7 "ruler"/cm pada font
    // isi 11pt Arial Narrow). Pemenggalan HANYA di koma.
    private const ADDR_LINE_CHARS = 55;

    /**
     * Pecah alamat jadi baris-baris pendek. Aturan: baris baru HANYA setelah
     * koma; tiap baris dijaga <= ADDR_LINE_CHARS karakter (segmen tunggal yang
     * lebih panjang dari itu tetap 1 baris utuh — tak ada koma untuk memenggal).
     */
    private function addressLines(?string $addr): array
    {
        $addr = trim(preg_replace('/\s+/', ' ', (string) $addr));
        if ($addr === '') {
            return ['..............................................', '..............................................'];
        }

        $segments = array_values(array_filter(
            array_map('trim', preg_split('/,\s*/', $addr)),
            fn ($s) => $s !== ''
        ));

        $lines = [];
        $cur = '';
        $n = count($segments);
        foreach ($segments as $i => $seg) {
            $piece = $seg . ($i < $n - 1 ? ',' : '');
            if ($cur === '') {
                $cur = $piece;
            } elseif (mb_strlen($cur . ' ' . $piece) <= self::ADDR_LINE_CHARS) {
                $cur .= ' ' . $piece;
            } else {
                $lines[] = $cur;
                $cur = $piece;
            }
        }
        if ($cur !== '') {
            $lines[] = $cur;
        }

        return $lines ?: [$addr];
    }

    private function flatAddress(?string $addr): string
    {
        return trim(preg_replace('/\s*\R\s*/', ', ', (string) $addr));
    }
}
