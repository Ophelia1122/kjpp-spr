<?php

namespace App\Services;

use App\Helpers\AddressFormatter;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Surat Tugas versi .docx (2026-09-14, feedback user) — isi & tata letak
 * mengikuti PDF (resources/views/pdf/surat-tugas.blade.php) supaya staf bisa
 * menyesuaikan isinya di Word sebelum dicetak/dikirim.
 */
class SuratTugasDocxBuilder
{
    private const FONT    = 'Arial Narrow';
    private const SIZE    = 11;
    private const SIDE_CM = 1.9;

    private PhpWord $word;

    public function __construct(private Project $project)
    {
        $this->project->loadMissing(
            'instructingClient', 'valuationObjects.appraisers', 'appraisers', 'assignmentStaff.user', 'signedBy',
            'assignmentLetterRecipientClient', 'assignmentLetterOnBehalfClient'
        );
    }

    /** Bangun .docx ke file sementara, kembalikan path-nya. */
    public function save(): string
    {
        // PhpWord tidak meng-escape teks secara default -> '&' pada nama klien merusak XML.
        Settings::setOutputEscapingEnabled(true);

        $this->word = new PhpWord();
        $this->word->setDefaultFontName(self::FONT);
        $this->word->setDefaultFontSize(self::SIZE);

        $section = $this->word->addSection([
            'pageSizeW'    => Converter::cmToTwip(21.0),
            'pageSizeH'    => Converter::cmToTwip(29.7),
            'marginTop'    => Converter::cmToTwip(0.65),
            'marginBottom' => Converter::cmToTwip(2.2),
            'marginLeft'   => Converter::cmToTwip(self::SIDE_CM),
            'marginRight'  => Converter::cmToTwip(self::SIDE_CM),
            'footerHeight' => Converter::cmToTwip(0.6),
        ]);

        $this->build($section);

        $path = storage_path('app/tmp/Surat-Tugas-' . uniqid() . '.docx');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        IOFactory::createWriter($this->word, 'Word2007')->save($path);

        return $path;
    }

    private function build(Section $s): void
    {
        $p       = $this->project;
        $content = 21.0 - self::SIDE_CM * 2;
        $noSpace = ['cellMarginTop' => 0, 'cellMarginBottom' => 0, 'cellMarginLeft' => 0, 'cellMarginRight' => 0];

        // ---------- Kop: logo panjang 125% + garis ----------
        $logo = public_path('images/logo-spr-long.png');
        if (is_file($logo)) {
            $w = Converter::cmToPoint(12.5);
            $s->addImage($logo, ['width' => $w, 'height' => $w / (1029 / 242), 'alignment' => Jc::CENTER]);
        }
        $s->addText('', ['size' => 2], ['spaceBefore' => 60, 'spaceAfter' => 160, 'borderBottomSize' => 12, 'borderBottomColor' => '1A1A1A']);

        // ---------- No. / Perihal (kiri) — Jakarta, tanggal (kanan) ----------
        $cols = [1.85, 0.35, 8.6, $content - 1.85 - 0.35 - 8.6];
        $t = $s->addTable($noSpace);
        $t->addRow();
        $this->text($t->addCell($this->cm($cols[0])), 'No.');
        $this->text($t->addCell($this->cm($cols[1])), ':');
        $this->text($t->addCell($this->cm($cols[2])), $p->assignment_letter_number ?: '-');
        $this->text($t->addCell($this->cm($cols[3])),
            'Jakarta, ' . ($p->assignment_letter_date ? $p->assignment_letter_date->translatedFormat('d F Y') : '-'),
            $this->f(), Jc::END);
        $t->addRow();
        $this->text($t->addCell($this->cm($cols[0])), 'Perihal');
        $this->text($t->addCell($this->cm($cols[1])), ':');
        $this->text($t->addCell($this->cm($cols[2])), 'Surat Tugas', $this->f(false, false, true));
        $t->addCell($this->cm($cols[3]));

        // ---------- Kepada Yth (alamat dipecah setelah koma tiap ±8 cm) ----------
        $recipient = $p->assignment_letter_recipient;
        $run = $s->addTextRun($this->para(Jc::START, 90, 160));
        $run->addText('Kepada Yth,', $this->f());
        $run->addTextBreak();
        $run->addText(mb_strtoupper((string) optional($recipient)->client_name), $this->f(true));
        $lines = AddressFormatter::lines(optional($recipient)->address);
        foreach ($lines ?: ['(alamat belum diisi pada data klien)'] as $line) {
            $run->addTextBreak();
            $run->addText($line, $this->f());
        }

        $s->addText('Dengan Hormat,', $this->f(), $this->para(Jc::START, 90));

        // ---------- Paragraf penugasan ----------
        $run = $s->addTextRun($this->para(Jc::BOTH, 90));
        $run->addText('Bersama ini kami menugaskan staff kami sebagai perwakilan ' . config('kjpp.company_name')
            . ' untuk melakukan Penilaian Aset atas nama ', $this->f());
        $run->addText($p->assignment_letter_on_behalf_name, $this->f(true));
        $run->addText('.', $this->f());
        if ($p->assignment_letter_request_basis_text !== '') {
            $run->addText(' ' . $p->assignment_letter_request_basis_text, $this->f());
        }
        $run->addText(' Berdasarkan Surat Penawaran ', $this->f());
        $run->addText('No. ' . $p->proposal_number . ' tanggal ' . ($p->proposal_date?->translatedFormat('d F Y') ?? ''), $this->f(true));
        $run->addText(' yang berupa:', $this->f());

        // ---------- Rincian objek (menjorok 1/3 tab) ----------
        $objCols = [0.42, 0.55, $content - 0.97];
        $t = $s->addTable($noSpace);
        foreach ($p->valuationObjects as $i => $object) {
            $desc = $object->assignment_letter_description_lines;
            $head = $desc[0] ?? 'Aset';
            $rest = array_slice($desc, 1);

            $t->addRow();
            $t->addCell($this->cm($objCols[0]));
            $this->text($t->addCell($this->cm($objCols[1])), ($i + 1) . '.');
            $cellRun = $t->addCell($this->cm($objCols[2]))->addTextRun($this->para(Jc::BOTH, 40));
            $cellRun->addText($head . ',', $this->f(true, true));
            $cellRun->addText(' ' . ($rest ? implode(', ', $rest) . ' ' : '') . 'yang berlokasi di ' . $object->location, $this->f());

        }

        $s->addText('dilaksanakan pada tanggal, '
            . ($p->survey_date ? \Carbon\Carbon::parse($p->survey_date)->translatedFormat('d F Y') : ''),
            $this->f(), $this->para(Jc::START, 30, 90));
        $s->addText('Adapun petugas kami adalah :', $this->f(), $this->para(Jc::START, 40));

        // ---------- Daftar petugas (menjorok ±1 cm) ----------
        $staffCols = [1.0, 0.45, 1.85, 0.35, $content - 3.65];
        $t = $s->addTable($noSpace);
        foreach ($p->assignmentStaff as $i => $staff) {
            foreach ([
                ['Nama', $staff->user->name ?? '-'],
                ['Jabatan', $staff->user->jabatan ?? '-'],
                ['No. MAPPI', $staff->user->mappi_no ?? '-'],
            ] as $row => [$label, $value]) {
                $t->addRow();
                $t->addCell($this->cm($staffCols[0]));
                $this->text($t->addCell($this->cm($staffCols[1])), $row === 0 ? (string) ($i + 1) : '');
                $this->text($t->addCell($this->cm($staffCols[2])), $label);
                $this->text($t->addCell($this->cm($staffCols[3])), ':');
                $this->text($t->addCell($this->cm($staffCols[4])), (string) $value);
            }
        }

        $s->addText('Surat Tugas ini sekaligus merupakan Berita Acara Pemeriksaan lapangan, mohon membubuhkan tanda tangan '
            . 'setelah staff / petugas kami selesai melakukan tugasnya, Demikian atas perhatian dan kerjasamanya kami '
            . 'sampaikan banyak terima kasih.', $this->f(), $this->para(Jc::BOTH, 90, 90));

        // ---------- Tanda tangan: barcode menempel ke nama ----------
        $s->addText('Hormat kami,', $this->f(), $this->para());
        $s->addText(config('kjpp.company_name'), $this->f(true), $this->para());

        $barcode = $p->assignment_letter_barcode && Storage::disk('public')->exists($p->assignment_letter_barcode)
            ? Storage::disk('public')->path($p->assignment_letter_barcode)
            : null;
        if ($barcode) {
            $size = Converter::cmToPoint(2.65);
            $s->addImage($barcode, ['width' => $size, 'height' => $size, 'alignment' => Jc::START]);
        } else {
            $s->addTextBreak(3, $this->f());
        }

        $s->addText(($p->signedBy->name ?? config('kjpp.signatory.name')), $this->f(true, false, true), $this->para());
        $s->addText($p->signedBy->partner_status ?? config('kjpp.signatory.title'), $this->f(false, true), $this->para());

        $run = $s->addTextRun($this->para(Jc::START, 0, 120));
        $run->addText('Perhatian', $this->f(false, true, true, 10));
        $run->addText(' : Dilarang meminta atau menerima imbalan jasa dalam bentuk apapun diluar kontrak yang telah di setujui. '
            . 'Apabila petugas lapangan kami meminta sesuatu kepada pihak klien/nasabah maka mohon dilaporkan kepada kami.',
            $this->f(false, true, false, 10));

        // ---------- Footer alamat kantor (tanpa garis) ----------
        $footer = $s->addFooter();
        foreach (config('kjpp.footer.lines') as $line) {
            $footer->addText($line, ['name' => self::FONT, 'size' => 7, 'color' => '222222'], $this->para(Jc::CENTER));
        }
    }

    // =====================================================================

    private function cm(float $cm): int
    {
        return (int) Converter::cmToTwip($cm);
    }

    private function f(bool $bold = false, bool $italic = false, bool $underline = false, float $size = self::SIZE): array
    {
        return array_filter([
            'name'      => self::FONT,
            'size'      => $size,
            'bold'      => $bold,
            'italic'    => $italic,
            'underline' => $underline ? 'single' : null,
            'color'     => '1A1A1A',
        ], fn ($v) => $v !== null && $v !== false);
    }

    private function para(string $align = Jc::START, int $after = 0, int $before = 0): array
    {
        return ['alignment' => $align, 'spaceBefore' => $before, 'spaceAfter' => $after, 'lineHeight' => 1.0];
    }

    private function text($container, string $text, ?array $font = null, string $align = Jc::START): void
    {
        $container->addText($text, $font ?? $this->f(), $this->para($align));
    }
}
