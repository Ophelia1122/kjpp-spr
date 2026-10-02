<?php

namespace App\Services;

use App\Models\DeliveryReceipt;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Table;

/**
 * Tanda Terima Pengiriman Buku versi .docx — tata letak, warna, dan tulisan
 * disamakan dengan contoh kantor & versi PDF
 * (resources/views/pdf/tanda-terima.blade.php), 2026-09-23 feedback user.
 */
class TandaTerimaDocxBuilder
{
    /**
     * Sentimeter -> twip BULAT. PhpWord menulis w:w/w:pgMar/w:pgSz apa
     * adanya tanpa pembulatan; Converter::cmToTwip() aslinya kembalikan
     * pecahan panjang (mis. 5669.291338582678), yang tidak valid menurut
     * skema OOXML (ST_TwipsMeasure harus bilangan bulat). LibreOffice
     * memaafkannya, Microsoft Word tidak selalu — tabel dan margin halaman
     * bisa berantakan hanya saat dibuka di Word (2026-09-28, bug ditemukan
     * dari berkas hasil generate yang dikirim user).
     */
    private static function twip(float $cm): int
    {
        return (int) round(Converter::cmToTwip($cm));
    }

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
        // pageSizeW/H eksplisit: bawaan PhpWord ("DEFAULT_WIDTH") sendiri
        // berupa pecahan (11905.511811024), sama tidak validnya menurut
        // skema OOXML seperti Converter::cmToTwip() mentah (2026-09-28).
        $section = $word->addSection([
            'pageSizeW' => self::twip(21.0), 'pageSizeH' => self::twip(29.7),
            'marginTop' => self::twip(1.2), 'marginBottom' => self::twip(1.2),
            'marginLeft' => self::twip(2.5), 'marginRight' => self::twip(2.5),
        ]);

        $r         = $this->receipt;
        $recipient = $r->recipient;
        $name      = (string) ($recipient?->client_name ?: $r->project->effective_client_name);
        $lines     = collect(preg_split('/\r\n|\r|\n/', (string) ($recipient?->address ?? '')))
            ->map(fn ($l) => trim($l))->filter()->values()->all();
        $up        = $r->recipient_up ?: '-';
        $w         = self::twip(16);   // lebar isi halaman

        // ---------- Kotak utama ----------
        $box = $section->addTable([
            'borderSize' => 6, 'borderColor' => self::NAVY,
            'width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => self::twip(0.25),
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
        $left  = $meta->addCell(self::twip(8));
        $right = $meta->addCell(self::twip(8));

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
        $this->labelValue($right, 'Up', $up, 0.8);

        $cell->addTextBreak(1, $this->f);
        $cell->addText('Telah diterima beberapa dokumen Penilaian Aset dengan rincian sebagai berikut:', $this->fB, $this->p0);

        if ($r->note) {
            $noteTbl = $cell->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 0]);
            $noteTbl->addRow();
            $noteTbl->addCell(self::twip(0.8))->addText('-', $this->f, ['spaceBefore' => 80] + $this->p0);
            $noteTbl->addCell(self::twip(15))->addText($r->note, $this->f, ['alignment' => Jc::BOTH, 'spaceBefore' => 80] + $this->p0);
        }

        $cell->addTextBreak(1, $this->f);

        // Tabel dokumen: hanya baris header yang berwarna.
        $cols = [self::twip(1), self::twip(7.4), self::twip(1.6), self::twip(3), self::twip(3)];
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
        $sc = $sign->addCell(self::twip(6.4));
        $sc->addText('Diterima Oleh,', $this->fB, ['indentation' => ['left' => self::twip(0.6)]] + $this->p0);
        $sc->addTextBreak(3, $this->f);
        $sc->addText('( ________________ )', $this->f, $this->p0);

        $nc = $sign->addCell(self::twip(9.6));
        $warn = $nc->addTable([
            'borderSize' => 6, 'borderColor' => '9AA4B8',
            'width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 80,
        ]);
        $warn->addRow();
        $wc = $warn->addCell(self::twip(9.6));
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
            ->addText(mb_strtoupper($name), $this->fWhite + ['size' => 14], $this->p0);

        $slip->addRow();
        $body = $slip->addCell($w);
        foreach ($lines as $line) {
            $body->addText($line, $this->f, $this->p0);
        }
        $this->labelValue($body, 'Up', $up, 2.2, 12.7);
        if ($r->note) {
            $noteTbl = $body->addTable([
                'layout' => Table::LAYOUT_FIXED, 'unit' => 'dxa',
                'width'  => self::twip(15.2), 'cellMargin' => 0,
            ]);
            $noteTbl->addRow();
            $noteTbl->addCell(self::twip(2.2))->addText('Keterangan', $this->fB, $this->p0);
            $noteTbl->addCell(self::twip(0.3))->addText(':', $this->fB, $this->p0);
            $noteTbl->addCell(self::twip(12.7))->addText($r->note, $this->f, ['alignment' => Jc::BOTH] + $this->p0);
        }

        $slip->addRow(self::twip(0.4));
        $slip->addCell($w, ['bgColor' => self::NAVY])->addText('', $this->f, $this->p0);

        $path = storage_path('app/tmp/tanda-terima-' . uniqid() . '.docx');
        @mkdir(dirname($path), 0775, true);
        IOFactory::createWriter($word, 'Word2007')->save($path);

        return $path;
    }

    /**
     * Baris "Label : Nilai" dengan lebar label tetap. Titik dua berada di
     * kolomnya sendiri supaya sejajar antar-baris (2026-09-24, feedback user).
     */
    private function labelValue($container, string $label, string $value, float $labelCm = 3.4, float $valueCm = 4.2): void
    {
        // Lebar kolom dipatok (LAYOUT_FIXED); kalau dibiarkan persen, Word
        // membagi ulang kolomnya dan titik duanya jadi tidak sejajar.
        $t = $container->addTable([
            'layout'     => Table::LAYOUT_FIXED,
            'unit'       => 'dxa',
            'width'      => self::twip($labelCm + 0.3 + $valueCm),
            'cellMargin' => 0,
        ]);
        $t->addRow();
        $t->addCell(self::twip($labelCm))->addText($label, $this->fB, $this->p0);
        $t->addCell(self::twip(0.3))->addText(':', $this->fB, $this->p0);
        $t->addCell(self::twip($valueCm))->addText($value, $this->f, $this->p0);
    }
}
