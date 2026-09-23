<?php

namespace App\Services;

use App\Models\DeliveryReceipt;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Tanda Terima Pengiriman Buku versi .docx (2026-09-23, feedback user) —
 * isi & urutan sama dengan PDF (resources/views/pdf/tanda-terima.blade.php).
 */
class TandaTerimaDocxBuilder
{
    private PhpWord $word;

    public function __construct(private DeliveryReceipt $receipt)
    {
        $this->receipt->loadMissing('recipient', 'project.instructingClient', 'project.namedClient');
    }

    public function save(): string
    {
        Settings::setOutputEscapingEnabled(true);

        $this->word = new PhpWord();
        $font = ['name' => 'Arial', 'size' => 10.5];
        $bold = $font + ['bold' => true];

        $section = $this->word->addSection([
            'marginTop' => Converter::cmToTwip(1.3), 'marginBottom' => Converter::cmToTwip(1.5),
            'marginLeft' => Converter::cmToTwip(2), 'marginRight' => Converter::cmToTwip(2),
        ]);

        // Kop + garis tebal.
        $logo = public_path('images/logo-spr-long.png');
        if (is_file($logo)) {
            $section->addImage($logo, ['width' => Converter::cmToPoint(16), 'alignment' => Jc::CENTER]);
        }
        $section->addText(str_repeat('_', 82), ['name' => 'Arial', 'size' => 8], ['spaceAfter' => 160]);

        $section->addText('TANDA TERIMA', $font + ['bold' => true, 'size' => 15], ['alignment' => Jc::CENTER, 'spaceAfter' => 160]);

        $r = $this->receipt;
        $recipient = $r->recipient;
        $name      = $recipient?->client_name ?: $r->project->effective_client_name;

        $meta = $section->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
        $meta->addRow();
        $left  = $meta->addCell(Converter::cmToTwip(8.5));
        $right = $meta->addCell(Converter::cmToTwip(8));
        $left->addText('Nomor Pengiriman', $bold, ['spaceAfter' => 0]);
        $left->addText($r->number, $font, ['spaceAfter' => 120]);
        $right->addText('Tanggal Pengiriman', $bold, ['spaceAfter' => 0]);
        $right->addText($r->delivery_date->translatedFormat('d F Y'), $font, ['spaceAfter' => 120]);

        $left->addText('Penerima:', $bold, ['spaceAfter' => 0]);
        $left->addText((string) $name, $bold, ['spaceAfter' => 0]);
        foreach (preg_split('/\r\n|\r|\n/', (string) ($recipient?->address ?? '')) as $line) {
            if (trim($line) !== '') {
                $left->addText(trim($line), $font, ['spaceAfter' => 0]);
            }
        }
        $left->addText('Up: ' . ($r->recipient_up ?: '-'), $font, ['spaceAfter' => 0]);

        $right->addText('Pengirim:', $bold, ['spaceAfter' => 0]);
        $right->addText(config('kjpp.company_name'), $font, ['spaceAfter' => 0]);
        $right->addText(\Illuminate\Support\Str::after(config('kjpp.footer.lines.0'), 'Head Office: '), $font, ['spaceAfter' => 0]);

        $section->addTextBreak(1, $font);
        $section->addText('Telah diterima beberapa dokumen Penilaian Aset dengan rincian sebagai berikut:', $font, ['spaceAfter' => 120]);

        $table = $section->addTable([
            'borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 60,
            'width' => 100 * 50, 'unit' => 'pct',
        ]);
        $head = ['bgColor' => 'E8EEF7'];
        $table->addRow(null, ['tblHeader' => true]);
        $table->addCell(Converter::cmToTwip(1.2), $head)->addText('No.', $bold, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $table->addCell(Converter::cmToTwip(8.6), $head)->addText('Nama Dokumen', $bold, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $table->addCell(Converter::cmToTwip(3.2), $head)->addText('Qty', $bold, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $table->addCell(Converter::cmToTwip(2.5), $head)->addText('Jenis', $bold, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        foreach ($r->documentRows() as $i => $row) {
            $table->addRow();
            $table->addCell(Converter::cmToTwip(1.2))->addText((string) ($i + 1), $font, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $table->addCell(Converter::cmToTwip(8.6))->addText($row['label'], $font, ['spaceAfter' => 0]);
            $table->addCell(Converter::cmToTwip(3.2))->addText($row['qty'] . ' ' . $row['unit'], $font, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $table->addCell(Converter::cmToTwip(2.5))->addText($row['kind'], $font, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }

        if ($r->note) {
            $section->addTextBreak(1, $font);
            $run = $section->addTextRun(['alignment' => Jc::BOTH]);
            $run->addText('Keterangan: ', $bold);
            $run->addText($r->note, $font);
        }

        $section->addTextBreak(2, $font);
        $sign = $section->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
        $sign->addRow();
        $sign->addCell(Converter::cmToTwip(9))->addText('', $font);
        $cell = $sign->addCell(Converter::cmToTwip(7));
        $cell->addText('Diterima Oleh,', $font, ['spaceAfter' => 0]);
        $cell->addTextBreak(3, $font);
        $cell->addText('( _______________________ )', $font, ['spaceAfter' => 0]);

        $section->addTextBreak(1, $font);
        $section->addText('Perhatian.', $bold + ['size' => 9.5], ['spaceAfter' => 0]);
        $section->addText('Mohon sertakan nama jelas penerima dan tanggal penerimaan berkas. Terima kasih.',
            $font + ['size' => 9.5], ['spaceAfter' => 0]);

        $path = storage_path('app/tmp/tanda-terima-' . uniqid() . '.docx');
        @mkdir(dirname($path), 0775, true);
        \PhpOffice\PhpWord\IOFactory::createWriter($this->word, 'Word2007')->save($path);

        return $path;
    }
}
