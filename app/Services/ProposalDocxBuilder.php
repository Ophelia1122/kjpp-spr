<?php

namespace App\Services;

use App\Helpers\Terbilang;
use App\Models\Project;
use PhpOffice\PhpWord\Element\Footer;
use PhpOffice\PhpWord\Element\Header;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;

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

    // true tepat setelah sebuah heading di-emit: paragraf isi PERTAMA
    // sesudahnya dibuat "keepLines" supaya heading tidak menggantung
    // sendirian di dasar halaman (isi ikut pindah ke halaman berikutnya).
    private bool $keepWithHeading = false;

    // Nomor daftar bernomor DALAM satu bab. Di-reset ke 0 tiap kali
    // masuk bab baru (lihat sectionTitle) supaya penomoran mulai dari 1
    // lagi, tidak lanjut dari bab sebelumnya.
    private int $listNo = 0;

    public function __construct(private Project $project)
    {
        $this->project->loadMissing('instructingClient', 'intendedUsers', 'valuationObjects', 'signedBy');
        $this->cl  = config('proposal_clauses');
        $this->cfg = config('kjpp');
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

        \PhpOffice\PhpWord\IOFactory::createWriter($this->word, 'Word2007')->save($path);

        return $path;
    }

    public function safeName(): string
    {
        return 'Proposal-' . str_replace(['/', '\\', ' '], '-', $this->project->proposal_number);
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
        $this->para($this->cl['pembatalan'], null, $this->sectionTitle('Pembatalan Penugasan'));
        $this->para($this->cl['kerahasiaan'], null, $this->sectionTitle('Kerahasiaan Informasi'));
        $this->para($this->cl['pendamping'], null, $this->sectionTitle('Pendamping Lapangan'));
        $this->para($this->cl['berita_acara'], null, $this->sectionTitle('Berita Acara'));
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
        $t->addCell(Converter::cmToTwip(10))->addText('No. ' . $this->project->proposal_number, $this->fBody, ['spaceAfter' => 0]);
        $t->addCell(Converter::cmToTwip(6.5))->addText('Jakarta, ' . now()->translatedFormat('d F Y'), $this->fBody, ['alignment' => Jc::RIGHT, 'spaceAfter' => 0]);

        $this->s->addTextBreak(1);
        $pt = $this->project->instructingClient;
        $this->s->addText('Kepada Yth,', $this->fBody, ['spaceAfter' => 0]);
        $this->s->addText($pt->client_name, $this->fBold, ['spaceAfter' => 0]);
        foreach ($this->addressLines($pt->address) as $ln) {
            $this->s->addText($ln, $this->fBody, ['spaceAfter' => 0]);
        }

        $this->s->addTextBreak(1);
        $this->s->addText('Hal : Proposal Biaya Jasa Penilaian an. ' . $pt->client_name, $this->fBody, ['spaceAfter' => 120]);
        $this->s->addText('Dengan hormat,', $this->fBody, ['spaceAfter' => 120]);

        $basis = trim((string) $this->project->request_basis) ?: $this->cl['pembuka_basis_placeholder'];
        $this->para(strtr($this->cl['pembuka'], [':basis' => $basis, ':klien' => $pt->client_name]));
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
            'sk_menkeu_no' => $u->sk_menkeu_no ?: $cfg['sk_menkeu_no'],
            'ojk_kep_no'   => $u->ojk_kep_no ?: $cfg['ojk_kep_no'],
            'sttd_ojk_no'  => $u->sttd_ojk_no ?: $cfg['sttd_ojk_no'],
            'mappi_no'     => $u->mappi_no ?: $cfg['mappi_no'],
            'rmk_no'       => $u->rmk_no ?: $cfg['rmk_no'],
            'klasifikasi'  => $u->klasifikasi ?: $cfg['klasifikasi'],
        ];
    }

    private function sectionStatusPenilai(): void
    {
        $ps = $this->sectionTitle('Penjelasan Status Penilai');
        $sig = $this->signatory();
        $repl = [
            ':nama'        => $sig['name'],
            ':izin'        => $sig['izin_pp_no'],
            ':sk_menkeu'   => $sig['sk_menkeu_no'],
            ':ojk_kep'     => $sig['ojk_kep_no'],
            ':izin_usaha'  => $this->cfg['izin_usaha_no'],
            ':kepmenkeu'   => $this->cfg['kepmenkeu_no'],
            ':sttd_ojk'    => $this->cfg['sttd_ojk_no'],
        ];
        foreach ($this->cl['status_penilai'] as $i => $p) {
            $this->para(strtr($p, $repl), null, $i === 0 ? $ps : null);
        }
    }

    /**
     * Bab Identifikasi (Pemberi Tugas / Pengguna Laporan) — format 2 kolom:
     * kolom kiri = nomor + judul bab + sub-label; kolom kanan = nama pihak
     * (tebal) diikuti baris-baris alamat. Bila pihak lebih dari satu,
     * masing-masing jadi blok bertumpuk dengan jarak antar-blok.
     */
    private function sectionIdentifikasi(string $title, string $subLabel, array $parties, ?string $trailing = null): void
    {
        $this->secNo++;
        $this->listNo = 0;

        $t = $this->s->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
        $t->addRow(null, ['cantSplit' => true]);

        $left = $t->addCell(Converter::cmToTwip(6.4));
        $left->addText(
            $this->secNo . '. ' . $title,
            ['bold' => true, 'size' => $this->fSize + 2, 'name' => $this->fName],
            ['spaceBefore' => 220, 'spaceAfter' => 40]
        );
        $left->addText($subLabel, $this->fBody, ['spaceAfter' => 0]);

        $right = $t->addCell(Converter::cmToTwip(9.5));
        foreach (array_values($parties) as $i => $p) {
            $lead = ['spaceBefore' => $i === 0 ? 220 : 160];
            $right->addText($p['name'], $this->fBold, ['spaceAfter' => 0] + $lead);
            foreach ($p['lines'] as $ln) {
                $right->addText($ln, $this->fBody, ['spaceAfter' => 0]);
            }
        }

        if ($trailing !== null && $trailing !== '') {
            $this->para($trailing);
        }
    }

    private function sectionPemberiTugas(): void
    {
        $pt = $this->project->instructingClient;
        $this->sectionIdentifikasi('Identifikasi Pemberi Tugas', 'Pemberi Tugas adalah', [
            ['name' => mb_strtoupper((string) $pt->client_name), 'lines' => $this->addressLines($pt->address)],
        ]);
    }

    private function sectionPenggunaLaporan(): void
    {
        $parties = [];
        foreach ($this->project->intendedUsers as $u) {
            $parties[] = ['name' => mb_strtoupper((string) $u->client_name), 'lines' => $this->addressLines($u->address)];
        }

        $trailing = $this->project->proposal_purpose === Project::PURPOSE_LK_PROPERTI
            ? strtr($this->cl['pengguna_laporan_lk_kap'], [':klien' => $this->project->instructingClient->client_name])
            : null;

        $this->sectionIdentifikasi('Identifikasi Pengguna Laporan', 'Pengguna Laporan adalah', $parties, $trailing);
    }

    private function sectionObjek(): void
    {
        $ps = $this->sectionTitle('Identifikasi Obyek Penilaian dan Kepemilikan');
        $this->para('Obyek Penilaian dalam lingkup penugasan ini adalah :', null, $ps);

        $tbl = $this->s->addTable([
            'borderSize' => 6, 'borderColor' => '000000',
            'width' => 100 * 50, 'unit' => 'pct',
            'cellMargin' => 60,
        ]);
        $hd = ['bold' => true, 'size' => 9];
        $cd = ['size' => 9];
        $tbl->addRow(null, ['tblHeader' => true]);
        $tbl->addCell(Converter::cmToTwip(0.9))->addText('No.', $hd);
        $tbl->addCell(Converter::cmToTwip(4.6))->addText('Jenis Aset/Properti', $hd);
        $tbl->addCell(Converter::cmToTwip(5.2))->addText('Lokasi', $hd);
        $tbl->addCell(Converter::cmToTwip(3.4))->addText('Bentuk/Jenis Hak Atas Tanah', $hd);
        $tbl->addCell(Converter::cmToTwip(3.0))->addText('Atas Nama', $hd);

        foreach ($this->project->valuationObjects as $i => $o) {
            $tbl->addRow();
            $tbl->addCell(Converter::cmToTwip(0.9))->addText((string) ($i + 1), $cd, ['alignment' => Jc::CENTER]);
            $jenis = $tbl->addCell(Converter::cmToTwip(4.6));
            foreach ($o->description_lines as $ln) {
                $jenis->addText($ln, $cd, ['spaceAfter' => 0]);
            }
            $tbl->addCell(Converter::cmToTwip(5.2))->addText($o->location, $cd, ['spaceAfter' => 0]);
            $tbl->addCell(Converter::cmToTwip(3.4))->addText($o->ownership_form, $cd, ['spaceAfter' => 0]);
            $tbl->addCell(Converter::cmToTwip(3.0))->addText($o->owner_name, $cd, ['spaceAfter' => 0]);
        }

        $this->s->addTextBreak(1);

        $ownerNames = $this->project->valuationObjects->pluck('owner_name')
            ->map(fn ($n) => trim((string) $n))->filter()->unique()->implode('; ');
        $this->para(strtr($this->cl['post_objek_hubungan'], [
            ':nama_sertifikat' => $ownerNames ?: '---nama pada sertifikat---',
            ':pemberi_tugas'   => $this->project->instructingClient->client_name,
        ]));
        $this->para($this->cl['post_objek']);
    }

    private function sectionMataUang(): void
    {
        $ps = $this->sectionTitle('Jenis Mata Uang yang Digunakan');
        $this->para($this->cl['mata_uang'], null, $ps);
    }

    private function sectionMaksudTujuan(): void
    {
        $ps = $this->sectionTitle('Maksud dan Tujuan Penilaian');
        $p  = $this->project->proposal_purpose;
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

        $run = $this->s->addTextRun($this->pJustify);
        $run->addText('Maksud Penilaian: ', $this->fBold);
        $run->addText($maksud, $this->fBody);

        $run2 = $this->s->addTextRun($this->pJustify);
        $run2->addText('Tujuan Penilaian: ', $this->fBold);
        $run2->addText($tujuan, $this->fBody);
    }

    private function sectionDasarNilai(): void
    {
        $ps = $this->sectionTitle('Dasar Nilai');
        $this->para(strtr($this->cl['dasar_nilai_intro'], [':dasar' => $this->project->value_basis_label]), null, $ps);

        if ($this->project->primary_value_basis === 'Nilai Pasar') {
            $this->s->addText('NILAI PASAR (Market Value)', $this->fBold, ['spaceAfter' => 40, 'keepNext' => true]);
            $this->para($this->cl['def_nilai_pasar']);
        } else {
            $pojk = $this->project->shows_pojk28_clause ? $this->cl['pojk28_suffix'] : '';
            $this->s->addText('NILAI WAJAR (Fair Value)', $this->fBold, ['spaceAfter' => 40, 'keepNext' => true]);
            $this->para(strtr($this->cl['def_nilai_wajar'], [':pojk' => $pojk]));
        }

        if ($this->project->proposal_purpose === Project::PURPOSE_LELANG) {
            $this->s->addText('NILAI LIKUIDASI (Liquidation Value)', $this->fBold, ['spaceAfter' => 40, 'keepNext' => true]);
            $this->para($this->cl['def_nilai_likuidasi']);
            // Khusus Lelang: Waktu Ekspos jadi sub-bab bernomor tersendiri
            // (bukan sekadar sub-judul tebal di dalam "Dasar Nilai").
            $this->para($this->cl['def_waktu_ekspos'], null, $this->sectionTitle('Waktu Ekspos (Exposure Time)'));
        } elseif ($this->project->proposal_purpose === Project::PURPOSE_PENJAMINAN_UTANG) {
            $this->para($this->cl['pu_likuidasi_note']);
        }
    }

    private function sectionTanggalPenilaian(): void
    {
        $ps = $this->sectionTitle('Tanggal Penilaian');
        if ($this->project->proposal_purpose === Project::PURPOSE_LK_PROPERTI) {
            $tgl = $this->project->valuation_date?->translatedFormat('d F Y') ?? '…………………';
            foreach ($this->cl['tanggal_penilaian_lk'] as $i => $p) {
                $this->para(strtr($p, [':tgl' => $tgl]), null, $i === 0 ? $ps : null);
            }
        } else {
            $tgl = $this->project->valuation_date
                ? ' (tanggal penilaian: ' . $this->project->valuation_date->translatedFormat('d F Y') . ')'
                : '';
            $this->para(strtr($this->cl['tanggal_penilaian_umum'], [
                ':tgl'   => $tgl,
                ':dasar' => $this->project->value_basis_label,
            ]), null, $ps);
        }
    }

    private function sectionTKI(): void
    {
        $ps = $this->sectionTitle('Tingkat Kedalaman Investigasi');
        $this->para($this->cl['tki_intro'], null, $ps);

        $alasan = trim((string) ($this->project->limited_inspection_reason ?? ''))
            ?: $this->cl['tki_alasan_limited_placeholder'];
        foreach ($this->cl['tki_items'] as $it) {
            $this->listBullet(strtr($it, [':alasan_limited' => $alasan]));
        }

        // Blok non-destruktif per jenis aset — ikut kategori objek proposal.
        $blocks = [];
        if ($this->hasBangunan())                                   $blocks[] = 'tki_bangunan';
        if ($this->hasCategory('Personal Properti - Mesin dan Peralatan')) $blocks[] = 'tki_mesin';
        if ($this->hasCategory('Personal Properti - Kendaraan'))    $blocks[] = 'tki_kendaraan';
        if ($this->hasCategory('Personal Properti - Alat Berat'))   $blocks[] = 'tki_alat_berat';

        foreach ($blocks as $key) {
            foreach ($this->cl[$key] as $p) {
                $this->para($p);
            }
        }
    }

    private function sectionSifatSumber(): void
    {
        $ps = $this->sectionTitle('Sifat dan Sumber Informasi yang Dapat Diandalkan');
        $this->para($this->cl['sifat_sumber'], null, $ps);
    }

    private function sectionAsumsi(): void
    {
        $ps = $this->sectionTitle('Asumsi Umum dan Asumsi Khusus');
        $this->para($this->cl['asumsi_intro'], null, $ps);
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
        $ps = $this->sectionTitle('Persyaratan atas Persetujuan untuk Publikasi');
        $this->para($this->cl['publikasi'], null, $ps);
    }

    private function sectionKonfirmasiSPI(): void
    {
        $ps = $this->sectionTitle('Konfirmasi bahwa Penilaian dilakukan Berdasarkan SPI');
        $this->para(strtr($this->cl['konfirmasi_spi'], [':spi_code' => $this->cl['spi_code_default']]), null, $ps);
    }

    private function sectionLaporan(): void
    {
        $ps = $this->sectionTitle('Laporan Penilaian');
        $isLong = $this->project->report_style === Project::REPORT_LONG;
        $style  = $isLong ? $this->cl['style_long'] : $this->cl['style_short'];
        $draft  = $this->project->sla_draft_days;
        $final  = $this->project->sla_final_days;
        $total  = ($draft && $final) ? ($draft + $final) : null;

        $dt = fn ($n) => $n ? $n . ' (' . Terbilang::words($n) . ')' : '-- (----)';

        $this->para(strtr($this->cl['laporan_intro'], [
            ':style' => $style, ':total' => $dt($total),
        ]), null, $ps);
        $this->listBullet(strtr($this->cl['laporan_draft'], [':draft' => $dt($draft)]));
        $this->listBullet(strtr($this->cl['laporan_final'], [':final' => $dt($final)]));
        $this->para($this->cl['laporan_struktur_intro']);
        $this->listNo = 0;   // daftar struktur laporan mulai 'a' lagi
        foreach ($this->cl['laporan_struktur'] as $it) {
            $this->listBullet($it);
        }
        $this->para($this->cl['laporan_rangkap']);
    }

    private function sectionBatasanTanggungJawab(): void
    {
        $ps = $this->sectionTitle('Batasan atau Pengecualian atas Tanggung Jawab kepada Pihak selain Pemberi Tugas');
        $this->para($this->cl['batasan_tanggung_jawab'], null, $ps);
    }

    private function sectionKebenaranData(): void
    {
        $ps = $this->sectionTitle('Pernyataan Kebenaran Data dan Informasi yang Diberikan oleh Pemberi Tugas');
        $this->para($this->cl['kebenaran_data_intro'], null, $ps);
        foreach ($this->cl['kebenaran_data_items'] as $it) {
            $this->listNum($it);
        }
    }

    private function sectionPendekatan(): void
    {
        $ps = $this->sectionTitle('Pendekatan yang Digunakan');
        $this->para($this->cl['pendekatan_intro'], null, $ps);
        foreach ($this->cl['pendekatan_items'] as $it) {
            $run = $this->listItemRun();
            $run->addText($it['lead'] . ', ', $this->fBold);
            $run->addText($it['text'], $this->fBody);
        }
    }

    private function sectionKondisiPembatas(): void
    {
        $ps = $this->sectionTitle('Kondisi Pembatas Penilaian');
        $this->para($this->cl['kondisi_pembatas'], null, $ps);
    }

    /**
     * LAMPIRAN PERMINTAAN DATA - DATA — di paling akhir dokumen, setelah
     * blok tanda tangan (REV.1). BUKAN bab bernomor.
     */
    private function sectionLampiran(): void
    {
        // Lampiran Permintaan Data SELALU mulai di halaman baru tersendiri
        // (halaman terakhir), terpisah dari blok tanda tangan.
        $this->s->addPageBreak();
        $this->s->addText(
            $this->cl['lampiran_title'],
            ['bold' => true, 'size' => $this->fSize + 2, 'name' => $this->fName],
            ['spaceBefore' => 200, 'spaceAfter' => 160, 'alignment' => Jc::CENTER, 'keepNext' => true]
        );
        $this->para($this->cl['data_diperlukan_intro']);

        $items = $this->cl['data_diperlukan_items'];
        if ($this->project->proposal_purpose === Project::PURPOSE_LK_PROPERTI) {
            array_unshift($items, $this->cl['data_diperlukan_lk_extra']);
        }
        foreach ($items as $it) {
            $this->listBullet($it);
        }
    }

    private function sectionProsedur(): void
    {
        $ps = $this->sectionTitle('Prosedur Pelaksanaan Penugasan');
        $this->para($this->cl['prosedur_intro'], null, $ps);
        foreach ($this->cl['prosedur_items'] as $it) {
            $this->listNum($it);
        }
    }

    private function sectionBiaya(): void
    {
        $ps = $this->sectionTitle('Biaya Jasa Penilaian');
        $p      = $this->project;
        $total  = round($p->total_fee);          // angka final (gross), rupiah bulat
        $termin = round($total / 2);
        $pct    = rtrim(rtrim(number_format($p->fee_ppn_rate * 100, 2, ',', ''), '0'), ',');

        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.') . ',00';

        $this->para($this->cl['biaya_intro'], null, $ps);
        // Nominal biaya (+ terbilang) rata tengah.
        $this->s->addText($rp($total), $this->fBold, ['alignment' => Jc::CENTER, 'spaceAfter' => 20]);
        $this->s->addText('(' . Terbilang::make($total) . ')', ['italic' => true] + $this->fBody, ['alignment' => Jc::CENTER, 'spaceAfter' => 120]);

        $this->para($p->fee_ppn_included
            ? $this->cl['biaya_ppn_included']
            : strtr($this->cl['biaya_ppn_excluded'], [':pct' => $pct]));

        // Rincian Biaya (opsional) — Fee / Transport / PPN / Total.
        // Satu baris tabel (cantSplit) berisi 3 cell agar tak terbelah halaman.
        if ($p->fee_breakdown) {
            $this->s->addText($this->cl['biaya_rincian_label'], $this->fBold, ['spaceBefore' => 80, 'spaceAfter' => 40, 'keepNext' => true]);
            $rows = [
                ['Fee', $rp($p->fee_professional), false],
                ['Transport', $rp($p->transport_cost ?? 0), false],
                ['PPN ' . $pct . '%', $rp($p->fee_ppn_amount), false],
                ['Total', $rp($total), true],
            ];
            $rin = $this->s->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
            $rin->addRow(null, ['cantSplit' => true]);
            $cK = $rin->addCell(Converter::cmToTwip(2.6));
            $cC = $rin->addCell(Converter::cmToTwip(0.3));
            $cV = $rin->addCell(Converter::cmToTwip(5.0));
            foreach ($rows as [$k, $v, $bold]) {
                $f = $bold ? $this->fBold : $this->fBody;
                $cK->addText($k, $f, ['spaceAfter' => 0]);
                $cC->addText(':', $this->fBody, ['spaceAfter' => 0]);
                $cV->addText($v, $f, ['spaceAfter' => 0]);
            }
            $this->s->addTextBreak(1);
        }

        // Termin pembayaran rata kiri.
        $this->s->addText($this->cl['termin_label'], $this->fBold, ['alignment' => Jc::START, 'spaceAfter' => 20]);
        $this->listNum(strtr($this->cl['termin_1'], [':rp' => $rp($termin), ':terbilang' => Terbilang::make($termin)]), Jc::START);
        $this->listNum(strtr($this->cl['termin_2'], [':rp' => $rp($total - $termin), ':terbilang' => Terbilang::make($total - $termin)]), Jc::START);

        // Rekening Bank & NPWP dalam 2 kolom: kiri = rekening (bisa 1–2),
        // kanan = NPWP (baku, dari config). Titik-dua dirapikan via kvTable.
        $this->s->addText($this->cl['rekening_label'], $this->fBold, ['spaceAfter' => 40]);

        $rt = $this->s->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
        $rt->addRow(null, ['cantSplit' => true]);

        $lc = $rt->addCell(Converter::cmToTwip(9.5));
        foreach (array_values($this->bankAccounts()) as $i => $b) {
            if ($i > 0) {
                $lc->addTextBreak(1);
            }
            $this->kvTable($lc, [
                ['Bank', $b['bank_name'] ?? '-'],
                ['Atas Nama', $b['account_name'] ?? '-'],
                ['No. Rek', $b['account_number'] ?? '-'],
            ], 2.4, 6.4);
        }

        $rc = $rt->addCell(Converter::cmToTwip(6.4));
        $this->kvTable($rc, [['NPWP No.', $this->cfg['npwp']]], 2.2, 3.6);

        $this->s->addTextBreak(1);
        $this->para($this->cl['biaya_pembatalan']);
    }

    private function sectionPernyataanPemberiTugas(): void
    {
        $this->sectionTitle('Pernyataan Pemberi Tugas');
        // Bab 25 + blok tanda tangan diikat dengan keepNext supaya blok
        // tanda tangan tidak berdiri sendiri di halaman terakhir isi —
        // minimal bab 25 ikut di halaman yang sama (bab 24 ikut kalau muat).
        $this->para($this->cl['pernyataan_pemberi_tugas'], null, ['keepNext' => true]);
        $this->para($this->cl['penutup_spk'], null, ['keepNext' => true]);
    }

    private function sectionTandaTangan(): void
    {
        // Paragraf jeda ber-keepNext = jembatan supaya blok tanda tangan
        // (tabel di bawah) menempel dengan Bab 25.
        $this->s->addText('', $this->fBody, ['spaceBefore' => 480, 'spaceAfter' => 0, 'keepNext' => true]);

        $sig      = $this->signatory();
        $approver = trim((string) $this->project->approver_name)
            ?: $this->project->instructingClient->client_name;

        // cantSplit: blok tanda tangan tidak boleh terbelah dua halaman.
        $t = $this->s->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
        $t->addRow(null, ['cantSplit' => true]);

        // Kolom kiri (blok KJPP/penandatangan) sengaja dilebarkan supaya
        // kolom kanan ("Menyetujui," dst) bergeser ke kanan & tidak
        // menempel dengan blok tanda tangan Penilai.
        $l = $t->addCell(Converter::cmToTwip(9.9));
        $l->addText('Hormat kami,', $this->fBody, ['spaceAfter' => 0]);
        $l->addText(strtoupper($this->cfg['company_name']), $this->fBold, ['spaceAfter' => 0]);
        $l->addText($this->cfg['company_tagline'], $this->fBody, ['spaceAfter' => 0]);
        $l->addTextBreak(7); // ruang tanda tangan + stempel
        $l->addText($sig['name'] . ', MAPPI (Cert.)', $this->fBold, ['spaceAfter' => 0]);
        $l->addText($sig['title'], $this->fBody, ['spaceAfter' => 0]);
        $l->addText('Penilai Properti Izin Menkeu No. : ' . $sig['izin_pp_no'], $this->fBody, ['spaceAfter' => 0]);
        $l->addText('MAPPI No. ' . $sig['mappi_no'], $this->fBody, ['spaceAfter' => 0]);
        $l->addText($sig['rmk_no'], $this->fBody, ['spaceAfter' => 0]);
        $l->addText('Surat Tanda Terdaftar OJK No. ' . $sig['sttd_ojk_no'], $this->fBody, ['spaceAfter' => 0]);
        $l->addText($sig['klasifikasi'], $this->fBody, ['spaceAfter' => 0]);

        $r = $t->addCell(Converter::cmToTwip(6.0));
        $r->addText('Menyetujui,', $this->fBody, ['spaceAfter' => 0]);
        $r->addText($approver, $this->fBold, ['spaceAfter' => 0]);
        $r->addTextBreak(7); // ruang tanda tangan + stempel
        $r->addText('( _______________________________ )', $this->fBody, ['spaceAfter' => 0]);
        $r->addText('Jabatan:', $this->fBody, ['spaceAfter' => 0]);
        $r->addText('Tanggal:', $this->fBody, ['spaceAfter' => 0]);
    }

    // ---------- helpers ----------

    /** Emit heading bernomor (font lebih besar dari isi), kembalikan null. */
    private function sectionTitle(string $title): ?array
    {
        $this->secNo++;
        $this->s->addText(
            $this->secNo . '. ' . $title,
            ['bold' => true, 'size' => $this->fSize + 2, 'name' => $this->fName],
            ['spaceBefore' => 220, 'spaceAfter' => 70, 'keepNext' => true, 'keepLines' => true]
        );
        $this->keepWithHeading = true;
        $this->listNo = 0;   // penomoran daftar mulai dari 1 lagi di bab ini
        return null;
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

    private function para(string $text, ?array $font = null, ?array $pExtra = null): void
    {
        $pStyle = $this->pJustify;
        if ($this->keepWithHeading) {
            $pStyle['keepLines'] = true;   // heading + paragraf ini tidak terpisah halaman
            $this->keepWithHeading = false;
        }
        if ($pExtra) {
            $pStyle = $pExtra + $pStyle;   // override (mis. 'keepNext' => true)
        }
        $this->s->addText($text, $font ?? $this->fBody, $pStyle);
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

    /** Gaya paragraf item daftar huruf (hanging indent, jarak 0 pt). */
    private function listPara(?string $align = null): array
    {
        return [
            'alignment'   => $align ?? Jc::BOTH,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'indentation' => ['left' => 397, 'hanging' => 397],
        ];
    }

    /**
     * Item daftar dengan penanda HURUF (a, b, c, …) — bukan bullet / angka.
     * Penomoran MANUAL & di-reset ke 'a' tiap bab (lihat sectionTitle).
     * Jarak antar-item 0 pt.
     */
    private function listItem(string $text, ?string $align = null): void
    {
        $this->listNo++;
        $this->s->addText(
            $this->alphaMarker($this->listNo) . '.   ' . $text,
            $this->fBody,
            $this->listPara($align)
        );
    }

    /** Item daftar huruf yang isinya campuran run (tebal + biasa). */
    private function listItemRun(): \PhpOffice\PhpWord\Element\TextRun
    {
        $this->listNo++;
        $run = $this->s->addTextRun($this->listPara());
        $run->addText($this->alphaMarker($this->listNo) . '.   ', $this->fBody);
        return $run;
    }

    // Alias supaya call-site lama tetap jalan — keduanya kini daftar huruf.
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
    private function kvTable($container, array $rows, float $labelCm = 2.4, float $valCm = 6.4): void
    {
        $t = $container->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
        foreach ($rows as [$k, $v]) {
            $t->addRow();
            $t->addCell(Converter::cmToTwip($labelCm))->addText($k, $this->fBody, ['spaceAfter' => 0]);
            $t->addCell(Converter::cmToTwip(0.3))->addText(':', $this->fBody, ['spaceAfter' => 0]);
            $t->addCell(Converter::cmToTwip($valCm))->addText((string) $v, $this->fBody, ['spaceAfter' => 0]);
        }
    }

    /**
     * Daftar rekening bank untuk blok Biaya. Sementara dari config; nanti
     * (fitur bank / Batch 4) diambil dari rekening yang dipilih di proposal
     * (bisa 1–2). Menerima config('kjpp.bank_accounts') (array) bila ada.
     */
    private function bankAccounts(): array
    {
        $accs = $this->cfg['bank_accounts'] ?? null;
        if (is_array($accs) && $accs !== []) {
            return $accs;
        }
        return [$this->cfg['bank_account']];
    }

    private function addressLines(?string $addr): array
    {
        $addr = trim((string) $addr);
        if ($addr === '') {
            return ['..............................................', '..............................................'];
        }
        return preg_split('/\r\n|\r|\n/', $addr) ?: [$addr];
    }

    private function flatAddress(?string $addr): string
    {
        return trim(preg_replace('/\s*\R\s*/', ', ', (string) $addr));
    }
}
