<?php

namespace App\Services;

use App\Helpers\Terbilang;
use App\Models\Project;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\ListItem;

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

    public function __construct(private Project $project)
    {
        $this->project->loadMissing('instructingClient', 'intendedUsers', 'valuationObjects');
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

        $font = $this->cfg['pdf_font'] ?: 'Arial';

        $this->word = new PhpWord();
        $this->word->setDefaultFontName($font);
        $this->word->setDefaultFontSize(10);

        $this->fBody    = ['name' => $font, 'size' => 10];
        $this->fBold    = ['name' => $font, 'size' => 10, 'bold' => true];
        $this->pJustify = ['alignment' => Jc::BOTH, 'spaceAfter' => 120, 'spaceBefore' => 0];

        $this->s = $this->word->addSection([
            'pageSizeW'    => Converter::cmToTwip(21.0),
            'pageSizeH'    => Converter::cmToTwip(29.7),
            'marginTop'    => Converter::cmToTwip(3.0),
            'marginBottom' => Converter::cmToTwip(2.8),
            'marginLeft'   => Converter::cmToTwip(2.2),
            'marginRight'  => Converter::cmToTwip(2.2),
            'headerHeight' => Converter::cmToTwip(1.6),
            'footerHeight' => Converter::cmToTwip(1.4),
        ]);

        $this->buildHeader();
        $this->buildFooter();

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
        $this->sectionDataDiperlukan();
        $this->sectionProsedur();
        $this->para($this->cl['pembatalan'], null, $this->sectionTitle('Pembatalan Penugasan'));
        $this->para($this->cl['kerahasiaan'], null, $this->sectionTitle('Kerahasiaan Informasi'));
        $this->para($this->cl['pendamping'], null, $this->sectionTitle('Pendamping Lapangan'));
        $this->para($this->cl['berita_acara'], null, $this->sectionTitle('Berita Acara'));
        $this->sectionBiaya();
        $this->sectionPernyataanPemberiTugas();
        $this->sectionTandaTangan();
    }

    // ---------- kop & footer ----------

    private function buildHeader(): void
    {
        $h = $this->s->addHeader();
        $t = $h->addTable(['width' => 100 * 50, 'unit' => 'pct']);
        $t->addRow();
        $logo = $t->addCell(Converter::cmToTwip(2.4));
        if (is_file($this->cfg['company_logo'])) {
            $logo->addImage($this->cfg['company_logo'], ['width' => 60, 'height' => 60]);
        }
        $mid = $t->addCell(Converter::cmToTwip(14.0));
        $mid->addText(strtoupper($this->cfg['company_name']), ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $mid->addText($this->cfg['company_tagline'], ['size' => 8], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $mid->addText('Izin Usaha KJPP No. ' . $this->cfg['izin_usaha_no'], ['size' => 8], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $t->addCell(Converter::cmToTwip(2.4));
        $h->addText(str_repeat('_', 120), ['size' => 4], ['spaceAfter' => 0]);
    }

    private function buildFooter(): void
    {
        $f = $this->s->addFooter();
        $small = ['size' => 6.5, 'color' => '333333'];
        $ps    = ['spaceAfter' => 0, 'spaceBefore' => 0];
        $f->addText(str_repeat('_', 120), ['size' => 4], $ps);
        foreach (['head_office', 'phone', 'website', 'emails', 'branches'] as $k) {
            $f->addText($this->cfg['footer'][$k], $small, $ps);
        }
        $f->addPreserveText('Halaman {PAGE}', ['size' => 7], ['alignment' => Jc::RIGHT, 'spaceBefore' => 40]);
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

    private function sectionStatusPenilai(): void
    {
        $ps = $this->sectionTitle('Penjelasan Status Penilai');
        $sig = $this->cfg['signatory'];
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

    private function sectionPemberiTugas(): void
    {
        $pt = $this->project->instructingClient;
        $ps = $this->sectionTitle('Identifikasi Pemberi Tugas ' . $pt->client_name);
        $desc = $pt->client_name . ($pt->address ? ', berkedudukan di ' . $this->flatAddress($pt->address) : '');
        $this->para(strtr($this->cl['pemberi_tugas_intro'], [':desc' => $desc]), null, $ps);
    }

    private function sectionPenggunaLaporan(): void
    {
        $ps = $this->sectionTitle('Identifikasi Pengguna Laporan');
        $this->para($this->cl['pengguna_laporan_intro'], null, $ps);
        foreach ($this->project->intendedUsers as $u) {
            $line = $u->client_name . ($u->address ? ', berkedudukan di ' . $this->flatAddress($u->address) : '');
            $this->listNum($line);
        }
        if ($this->project->proposal_purpose === Project::PURPOSE_LK_PROPERTI) {
            $this->para(strtr($this->cl['pengguna_laporan_lk_kap'], [':klien' => $this->project->instructingClient->client_name]));
        }
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
            $this->s->addText('NILAI PASAR (Market Value)', $this->fBold, ['spaceAfter' => 40]);
            $this->para($this->cl['def_nilai_pasar']);
        } else {
            $pojk = $this->project->shows_pojk28_clause ? $this->cl['pojk28_suffix'] : '';
            $this->s->addText('NILAI WAJAR (Fair Value)', $this->fBold, ['spaceAfter' => 40]);
            $this->para(strtr($this->cl['def_nilai_wajar'], [':pojk' => $pojk]));
        }

        if ($this->project->proposal_purpose === Project::PURPOSE_LELANG) {
            $this->s->addText('NILAI LIKUIDASI (Liquidation Value)', $this->fBold, ['spaceAfter' => 40]);
            $this->para($this->cl['def_nilai_likuidasi']);
            $this->s->addText('Waktu Ekspos (Exposure Time)', $this->fBold, ['spaceAfter' => 40]);
            $this->para($this->cl['def_waktu_ekspos']);
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
        foreach ($this->cl['tki_items'] as $it) {
            $this->listBullet($it);
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
            $run = $this->s->addListItemRun(0, ['listType' => ListItem::TYPE_BULLET_FILLED], $this->pJustify);
            $run->addText($it['lead'] . ', ', $this->fBold);
            $run->addText($it['text'], $this->fBody);
        }
    }

    private function sectionKondisiPembatas(): void
    {
        $ps = $this->sectionTitle('Kondisi Pembatas Penilaian');
        $this->para($this->cl['kondisi_pembatas'], null, $ps);
    }

    private function sectionDataDiperlukan(): void
    {
        $this->sectionTitle('Data-data yang diperlukan');
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
        $fee    = (float) $this->project->service_fee;
        $termin = round($fee / 2);

        $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.') . ',00';

        $this->para($this->cl['biaya_intro'], null, $ps);
        $this->s->addText($rp($fee), $this->fBold, ['spaceAfter' => 20]);
        $this->s->addText('(' . Terbilang::make($fee) . ')', ['italic' => true] + $this->fBody, ['spaceAfter' => 120]);
        $this->para($this->cl['biaya_ppn']);

        $this->s->addText($this->cl['termin_label'], $this->fBold, ['spaceAfter' => 20]);
        $this->listNum(strtr($this->cl['termin_1'], [':rp' => $rp($termin), ':terbilang' => Terbilang::make($termin)]));
        $this->listNum(strtr($this->cl['termin_2'], [':rp' => $rp($fee - $termin), ':terbilang' => Terbilang::make($fee - $termin)]));

        $this->s->addText($this->cl['rekening_label'], $this->fBold, ['spaceAfter' => 20]);
        $bank = $this->cfg['bank_account'];
        $this->s->addText('Bank        : ' . $bank['bank_name'] . '   NPWP No. ' . $this->cfg['npwp'], $this->fBody, ['spaceAfter' => 0]);
        $this->s->addText('Atas Nama   : ' . $bank['account_name'], $this->fBody, ['spaceAfter' => 0]);
        $this->s->addText('No. Rek     : ' . $bank['account_number'], $this->fBody, ['spaceAfter' => 120]);

        $this->para($this->cl['biaya_pembatalan']);
    }

    private function sectionPernyataanPemberiTugas(): void
    {
        $ps = $this->sectionTitle('Pernyataan Pemberi Tugas');
        $this->para($this->cl['pernyataan_pemberi_tugas'], null, $ps);
        $this->para($this->cl['penutup_spk']);
    }

    private function sectionTandaTangan(): void
    {
        $this->s->addTextBreak(2);
        $sig = $this->cfg['signatory'];
        $t = $this->s->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
        $t->addRow();

        $l = $t->addCell(Converter::cmToTwip(8.3));
        $l->addText('Hormat kami,', $this->fBody, ['spaceAfter' => 0]);
        $l->addText(strtoupper($this->cfg['company_name']), $this->fBold, ['spaceAfter' => 0]);
        $l->addText($this->cfg['company_tagline'], $this->fBody, ['spaceAfter' => 0]);
        $l->addTextBreak(3);
        $l->addText($sig['name'] . ', MAPPI (Cert.)', $this->fBold, ['spaceAfter' => 0]);
        $l->addText($sig['title'], $this->fBody, ['spaceAfter' => 0]);
        $l->addText('Penilai Properti Izin Menkeu No. : ' . $sig['izin_pp_no'], $this->fBody, ['spaceAfter' => 0]);
        $l->addText('MAPPI No. ' . $sig['mappi_no'], $this->fBody, ['spaceAfter' => 0]);
        $l->addText($sig['rmk_no'], $this->fBody, ['spaceAfter' => 0]);
        $l->addText('Surat Tanda Terdaftar OJK No. ' . $sig['sttd_ojk_no'], $this->fBody, ['spaceAfter' => 0]);
        $l->addText($sig['klasifikasi'], $this->fBody, ['spaceAfter' => 0]);

        $r = $t->addCell(Converter::cmToTwip(8.3));
        $r->addText('Menyetujui,', $this->fBody, ['spaceAfter' => 0]);
        $r->addText($this->project->instructingClient->client_name, $this->fBold, ['spaceAfter' => 0]);
        $r->addTextBreak(4);
        $r->addText('( _______________________________ )', $this->fBody, ['spaceAfter' => 0]);
        $r->addText('Jabatan:', $this->fBody, ['spaceAfter' => 0]);
        $r->addText('Tanggal:', $this->fBody, ['spaceAfter' => 0]);

        if ($this->project->shows_bank_acknowledgement) {
            $this->s->addTextBreak(2);
            $this->s->addText('Mengetahui,', $this->fBody, ['spaceAfter' => 0]);
            $this->s->addText($this->project->instructingClient->client_name, $this->fBold, ['spaceAfter' => 0]);
            $this->s->addTextBreak(3);
            $this->s->addText('( _______________________________ )', $this->fBody, ['spaceAfter' => 0]);
            $this->s->addText('Jabatan:', $this->fBody, ['spaceAfter' => 0]);
            $this->s->addText('Tanggal:', $this->fBody, ['spaceAfter' => 0]);
        }
    }

    // ---------- helpers ----------

    /** Emit heading bernomor, kembalikan null (heading sudah ditulis). */
    private function sectionTitle(string $title): ?array
    {
        $this->secNo++;
        $this->s->addText(
            $this->secNo . '. ' . $title,
            ['bold' => true, 'size' => 10.5, 'name' => $this->cfg['pdf_font'] ?: 'Arial'],
            ['spaceBefore' => 200, 'spaceAfter' => 60, 'keepNext' => true]
        );
        return null;
    }

    private function para(string $text, ?array $font = null, $unused = null): void
    {
        $this->s->addText($text, $font ?? $this->fBody, $this->pJustify);
    }

    private function listNum(string $text): void
    {
        $this->s->addListItem($text, 0, $this->fBody, ['listType' => ListItem::TYPE_NUMBER], $this->pJustify);
    }

    private function listBullet(string $text): void
    {
        $this->s->addListItem($text, 0, $this->fBody, ['listType' => ListItem::TYPE_BULLET_FILLED], $this->pJustify);
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
