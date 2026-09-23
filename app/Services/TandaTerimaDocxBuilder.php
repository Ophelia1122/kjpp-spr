<?php

namespace App\Services;

use App\Models\DeliveryReceipt;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Tanda Terima Pengiriman Buku versi .docx — tata letak, warna, dan tulisan
 * disamakan dengan contoh kantor & versi PDF
 * (resources/views/pdf/tanda-terima.blade.php), 2026-09-23 feedback user.
 */
class TandaTerimaDocxBuilder
{
    private const NAVY = '001F60';
    private const FONT = 'Arial Narrow';

    private array $f     = ['name' => self::FONT, 'size' => 11];
    private array $fB    = ['name' => self::FONT, 'size' => 11, 'bold' => true];
    private array $fWhite;
    private array $p0    = ['spaceAfter' => 0, 'spaceBefore' => 0];

    public function __construct(private DeliveryReceipt $receipt)
    {
        $this->receipt->loadMissing('recipient', 'project.instructingClient', 'project.namedClient');
        $this->fWhite = $this->fB + ['color' => 'FFFFFF'];
    }

    public function save(): string
    {
        Settings::setOutputEscapingEnabled(true);

        $word    = new PhpWord();
        $section = $word->addSection([
            'marginTop' => Converter::cmToTwip(1.2), 'marginBottom' => Converter::cmToTwip(1.2),
            'marginLeft' => Converter::cmToTwip(2.5), 'marginRight' => Converter::cmToTwip(2.5),
        ]);

        $r         = $this->receipt;
        $recipient = $r->recipient;
        $name      = (string) ($recipient?->client_name ?: $r->project->effective_client_name);
        $lines     = collect(preg_split('/\r\n|\r|\n/', (string) ($recipient?->address ?? '')))
            ->map(fn ($l) => trim($l))->filter()->values()->all();
        $up        = $r->recipient_up ?: '-';
        $w         = Converter::cmToTwip(16);   // lebar isi halaman

        // ---------- Kotak utama ----------
        $box = $section->addTable([
            'borderSize' => 6, 'borderColor' => self::NAVY,
            'width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => Converter::cmToTwip(0.25),
        ]);
        $box->addRow();
        $cell = $box->addCell($w);

        $logo = public_path('images/logo-spr-short.png');
        if (is_file($logo)) {
            $cell->addImage($logo, ['width' => Converter::cmToPoint(10.4), 'alignment' => Jc::START]);
        }

        // Bar judul.
        $bar = $cell->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 60]);
        $bar->addRow();
        $bar->addCell($w, ['bgColor' => self::NAVY])
            ->addText('TANDA TERIMA', $this->fWhite + ['size' => 13], ['alignment' => Jc::CENTER] + $this->p0);

        $cell->addTextBreak(1, $this->f);

        // Nomor/tanggal + penerima.
        $meta = $cell->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
        $meta->addRow();
        $left  = $meta->addCell(Converter::cmToTwip(8));
        $right = $meta->addCell(Converter::cmToTwip(8));

        $this->labelValue($left, 'Nomor Pengiriman', $r->number);
        $this->labelValue($left, 'Tanggal Pengiriman', $r->delivery_date->translatedFormat('d F Y'));
        $left->addTextBreak(1, $this->f);
        $left->addText('Pengirim:', $this->fB, $this->p0);
        $left->addText('KJPP Sugianto Prasodjo dan Rekan', $this->f, $this->p0);

        $right->addText('Penerima:', $this->fB, $this->p0);
        $right->addText($name, $this->fB, $this->p0);
        foreach ($lines as $line) {
            $right->addText($line, $this->f, $this->p0);
        }
        $this->labelValue($right, 'Up:', $up, 1.2);

        $cell->addTextBreak(1, $this->f);
        $cell->addText('Telah diterima beberapa dokumen Penilaian Aset dengan rincian sebagai berikut:', $this->fB, $this->p0);

        if ($r->note) {
            $noteTbl = $cell->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
            $noteTbl->addRow();
            $noteTbl->addCell(Converter::cmToTwip(0.8))->addText('-', $this->f, ['spaceBefore' => 80] + $this->p0);
            $noteTbl->addCell(Converter::cmToTwip(15))->addText($r->note, $this->f, ['alignment' => Jc::BOTH, 'spaceBefore' => 80] + $this->p0);
        }

        $cell->addTextBreak(1, $this->f);

        // Tabel dokumen: hanya baris header yang berwarna.
        $cols = [Converter::cmToTwip(1), Converter::cmToTwip(7.4), Converter::cmToTwip(1.6), Converter::cmToTwip(3), Converter::cmToTwip(3)];
        $docs = $cell->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 60]);
        $docs->addRow(null, ['tblHeader' => true]);
        $head = ['bgColor' => self::NAVY];
        $docs->addCell($cols[0], $head)->addText('No.', $this->fWhite, $this->p0);
        $docs->addCell($cols[1], $head)->addText('Nama Dokumen', $this->fWhite, $this->p0);
        $docs->addCell($cols[2], $head)->addText('', $this->fWhite, $this->p0);
        $docs->addCell($cols[3], $head)->addText('Qty', $this->fWhite, $this->p0);
        $docs->addCell($cols[4], $head)->addText('Jenis', $this->fWhite, $this->p0);

        foreach ($r->documentRows() as $i => $row) {
            $docs->addRow();
            $docs->addCell($cols[0])->addText((string) ($i + 1), $this->f, ['alignment' => Jc::CENTER] + $this->p0);
            $docs->addCell($cols[1])->addText($row['label'], $this->f, $this->p0);
            $docs->addCell($cols[2])->addText("\u{2611}", $this->f, ['alignment' => Jc::CENTER] + $this->p0);
            $docs->addCell($cols[3])->addText($row['qty'] . ' ' . $row['unit'], $this->f, $this->p0);
            $docs->addCell($cols[4])->addText($row['kind'], $this->f, $this->p0);
        }

        $cell->addTextBreak(1, $this->f);

        // Tanda tangan + kotak "Perhatian".
        $sign = $cell->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
        $sign->addRow();
        $sc = $sign->addCell(Converter::cmToTwip(8.5));
        $sc->addText('Diterima Oleh,', $this->fB, ['indentation' => ['left' => Converter::cmToTwip(0.6)]] + $this->p0);
        $sc->addTextBreak(3, $this->f);
        $sc->addText('( ________________ )', $this->f, $this->p0);

        $nc = $sign->addCell(Converter::cmToTwip(7.5));
        $warn = $nc->addTable([
            'borderSize' => 6, 'borderColor' => '9AA4B8',
            'width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 80,
        ]);
        $warn->addRow();
        $wc = $warn->addCell(Converter::cmToTwip(7.5));
        $small = ['name' => self::FONT, 'size' => 9.5, 'italic' => true, 'color' => '5B6478'];
        foreach (['Perhatian.', 'Mohon sertakan nama jelas penerima dan tanggal penerimaan berkas.', 'Terima kasih.'] as $t) {
            $wc->addText($t, $small, $this->p0);
        }

        $section->addTextBreak(1, $this->f);

        // ---------- Potongan "Kepada Yth." ----------
        $slip = $section->addTable([
            'borderSize' => 6, 'borderColor' => self::NAVY,
            'width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 80,
        ]);
        $slip->addRow();
        $slip->addCell($w)->addText('Kepada Yth.', $this->fB, $this->p0);

        $slip->addRow();
        $slip->addCell($w, ['bgColor' => self::NAVY])
            ->addText(mb_strtoupper($name), $this->fWhite + ['size' => 14], ['alignment' => Jc::CENTER] + $this->p0);

        $slip->addRow();
        $body = $slip->addCell($w);
        foreach ($lines as $line) {
            $body->addText($line, $this->f, $this->p0);
        }
        $this->labelValue($body, 'Up:', $up, 1.2);
        if ($r->note) {
            $noteTbl = $body->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
            $noteTbl->addRow();
            $noteTbl->addCell(Converter::cmToTwip(2.2))->addText('Keterangan:', $this->fB, $this->p0);
            $noteTbl->addCell(Converter::cmToTwip(13))->addText($r->note, $this->f, ['alignment' => Jc::BOTH] + $this->p0);
        }

        $slip->addRow(Converter::cmToTwip(0.4));
        $slip->addCell($w, ['bgColor' => self::NAVY])->addText('', $this->f, $this->p0);

        $path = storage_path('app/tmp/tanda-terima-' . uniqid() . '.docx');
        @mkdir(dirname($path), 0775, true);
        IOFactory::createWriter($word, 'Word2007')->save($path);

        return $path;
    }

    /** Baris "Label   Nilai" dengan lebar label tetap. */
    private function labelValue($container, string $label, string $value, float $labelCm = 3.4): void
    {
        $t = $container->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
        $t->addRow();
        $t->addCell(Converter::cmToTwip($labelCm))->addText($label, $this->fB, $this->p0);
        $t->addCell(Converter::cmToTwip(4.5))->addText($value, $this->f, $this->p0);
    }
}
