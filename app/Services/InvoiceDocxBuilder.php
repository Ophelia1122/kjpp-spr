<?php

namespace App\Services;

use App\Helpers\Terbilang;
use App\Models\Invoice;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Invoice & Kwitansi versi .docx (2026-09-14, feedback user) — tata letak
 * mengikuti PDF (resources/views/pdf/invoice & pdf/_kwitansi_copy) supaya
 * staf bisa menyesuaikan isi yang dinamis di Word sebelum dikirim.
 *
 * GARIS TABEL: sama seperti PDF, kotak hanya punya bingkai luar + garis
 * horizontal tertentu, TANPA garis vertikal di tengah. Karena itu tabel
 * dibuat tanpa border, lalu tiap sel diberi garis per sisi lewat edges().
 */
class InvoiceDocxBuilder
{
    private const FONT = 'Arial Narrow';

    private PhpWord $word;
    private $project;

    public function __construct(private Invoice $invoice, private array $bank)
    {
        $this->invoice->loadMissing('project.instructingClient', 'project.valuationObjects', 'project.signedBy', 'receivedFromClient', 'onBehalfOfClient');
        $this->project = $this->invoice->project;
    }

    public function saveInvoice(): string
    {
        $section = $this->newDocument(1.5, 2.2, 1.9);
        $this->buildInvoice($section);

        return $this->write('Invoice');
    }

    public function saveKwitansi(): string
    {
        // Margin atas/bawah dipersempit supaya 2 lembar (dengan ruang tanda
        // tangan 8 baris) tetap muat satu halaman A4.
        $section = $this->newDocument(0.7, 0.6, 1.4);

        // Dua lembar identik dalam satu halaman, dipisah garis potong.
        $this->buildKwitansiCopy($section);
        $section->addText(
            str_repeat('- ', 38) . 'gunting di sini ' . str_repeat('- ', 38),
            $this->f(7, false, false, false, '666666'),
            ['alignment' => Jc::CENTER, 'spaceBefore' => 60, 'spaceAfter' => 60]
        );
        $this->buildKwitansiCopy($section);

        return $this->write('Kwitansi');
    }

    // =====================================================================
    // INVOICE
    // =====================================================================

    private function buildInvoice(Section $s): void
    {
        $inv     = $this->invoice;
        $p       = $this->project;
        $client  = $p->instructingClient;
        $content = 21.0 - 1.9 * 2;
        $leftW   = Converter::cmToTwip($content * 0.6);
        $rightW  = Converter::cmToTwip($content * 0.4);
        $margins = ['cellMarginLeft' => 140, 'cellMarginRight' => 140, 'cellMarginTop' => 80, 'cellMarginBottom' => 80];

        // Kop: logo panjang + garis tebal.
        $logo = public_path('images/logo-spr-long.png');
        if (is_file($logo)) {
            $w = Converter::cmToPoint(10.0);
            $s->addImage($logo, ['width' => $w, 'height' => $w / (1029 / 242), 'alignment' => Jc::CENTER]);
        }
        $s->addText('', ['size' => 2], ['spaceBefore' => 40, 'spaceAfter' => 240, 'borderBottomSize' => 18, 'borderBottomColor' => '000000']);
        $s->addText('I N V O I C E', $this->f(17, true), ['alignment' => Jc::CENTER, 'spaceAfter' => 200]);

        // Nomor invoice: kotak berbingkai sendiri, rata kanan, di luar tabel utama.
        $no = $s->addTable(array_merge($margins, ['alignment' => Jc::END]));
        $no->addRow();
        $run = $no->addCell($rightW, $this->edges(['top', 'bottom', 'left', 'right']))->addTextRun($this->p0(Jc::CENTER));
        $run->addText('No. ', $this->f());
        $run->addText($inv->invoice_number, $this->f(10.5, true));
        $s->addText('', ['size' => 6], $this->p0());

        $t = $s->addTable($margins);

        // Telah diterima Dari + Pemberi Tugas (selebar tabel)
        $t->addRow();
        $c = $t->addCell($leftW + $rightW, array_merge($this->edges(['top', 'bottom', 'left', 'right']), ['gridSpan' => 2]));
        // Pihak "Telah diterima dari" yang dipilih per invoice (2026-09-14).
        $payer = $inv->payer ?? $client;
        $this->p($c, 'Telah diterima Dari');
        $this->p($c, $payer->client_name, $this->f(10.5, true));
        foreach (preg_split('/\R/', (string) $payer->address) as $addr) {
            if (trim($addr) !== '') {
                $this->p($c, trim($addr), $this->f(9));
            }
        }

        // Judul kolom (garis bawah)
        $t->addRow();
        $this->p($t->addCell($leftW, $this->edges(['left', 'bottom'])), 'URAIAN / DESCRIPTION', $this->f(10.5, true));
        $this->p($t->addCell($rightW, $this->edges(['right', 'bottom'])), 'JUMLAH / AMOUNT', $this->f(10.5, true), Jc::END);

        // Uraian pembayaran (tanpa garis dalam)
        $t->addRow();
        $c = $t->addCell($leftW, $this->edges(['left']));
        $this->p($c, 'UNTUK PEMBAYARAN', $this->f(10.5, true, false, true));
        $this->p($c, 'FOR PAYMENT', $this->f(9, false, true));
        $this->p($c, '');
        $this->p($c, 'Pembayaran ' . ($inv->term_description ?: 'Biaya Jasa Penilaian')
            . ' Biaya Jasa Penilaian Properti an. ' . $inv->on_behalf_name . ' yang berlokasi di :', $this->f(10.5, true, true));
        $this->p($c, '');

        // Uraian lokasi 9,5pt, sedikit lebih kecil dari isi (2026-09-14, feedback user).
        if ($p->invoice_location_summary) {
            $this->p($c, $p->invoice_location_summary, $this->f(9.5));
        } else {
            foreach ($p->valuationObjects as $i => $object) {
                $this->p($c, ($i + 1) . '. ' . $object->location, $this->f(9.5), Jc::START, 60);
            }
            $this->p($c, '');
            $this->p($c, 'Sesuai dengan Surat Penawaran No. ' . $p->proposal_number);
            $this->p($c, 'Tanggal ' . $p->effective_proposal_date->translatedFormat('d F Y'));
        }

        $c = $t->addCell($rightW, array_merge($this->edges(['right']), ['valign' => 'top']));
        $this->p($c, 'Rp   ' . $this->rp($inv->net_amount), $this->f(), Jc::END);

        // Label "Ppn" dan nominalnya di baris yang SAMA supaya selalu sejajar
        // (2026-09-14, feedback user).
        $t->addRow();
        $this->p($t->addCell($leftW, $this->edges(['left'])), 'Ppn ' . $this->ppnPercent());
        $this->p($t->addCell($rightW, $this->edges(['right'])), 'Rp   ' . $this->rp($inv->ppn_amount), $this->f(), Jc::END);

        // Terbilang (garis atas)
        $t->addRow();
        $c = $t->addCell($leftW, $this->edges(['left', 'top']));
        $this->p($c, 'TERBILANG', $this->f(10.5, true, false, true));
        $this->p($c, 'THE AMOUNT OF', $this->f(9, false, true));
        $this->p($c, '');
        $this->p($c, '# ' . $this->terbilang($inv->amount) . ' #', $this->f(10.5, true, true));
        $t->addCell($rightW, $this->edges(['right', 'top']));

        // Total (garis atas + bingkai bawah)
        $t->addRow();
        $this->p($t->addCell($leftW, $this->edges(['left', 'top', 'bottom'])), 'TOTAL', $this->f(10.5, true), Jc::CENTER);
        $this->p($t->addCell($rightW, $this->edges(['right', 'top', 'bottom'])), 'Rp   ' . $this->rp($inv->amount), $this->f(10.5, true), Jc::END);

        // Rekening | tanda tangan
        $s->addText('', $this->f(), $this->p0());
        $b = $s->addTable(['cellMarginTop' => 0, 'cellMarginBottom' => 0]);
        $b->addRow();
        $c = $b->addCell(Converter::cmToTwip($content * 0.46));
        $this->p($c, 'Pembayaran mohon ditransfer ke Rekening:', $this->f(10));
        $this->p($c, $this->bank['bank_name'] ?? '-', $this->f(10.5, true));
        if (! empty($this->bank['branch'])) {
            $this->p($c, 'Cabang ' . $this->bank['branch'], $this->f(10.5, true));
        }
        $this->p($c, 'A/C. ' . ($this->bank['account_number'] ?? '-'), $this->f(10.5, true));
        $this->p($c, $this->bank['account_name'] ?? config('kjpp.company_name'), $this->f(10.5, true));

        $c = $b->addCell(Converter::cmToTwip($content * 0.54));
        $this->p($c, 'Jakarta, ' . $inv->display_date->translatedFormat('d F Y'), $this->f(), Jc::CENTER);
        // Ruang tanda tangan: 7 baris, sama dengan PDF.
        for ($i = 0; $i < 7; $i++) {
            $this->p($c, '', $this->f(), Jc::CENTER);
        }
        $this->p($c, $this->signerName(), $this->f(10, true), Jc::CENTER);
        $this->p($c, $this->signerTitle(), $this->f(), Jc::CENTER);

        // Footer alamat kantor.
        $footer = $s->addFooter();
        foreach (config('kjpp.footer.lines') as $footLine) {
            $footer->addText($footLine, $this->f(7, false, false, false, '333333'), $this->p0(Jc::CENTER));
        }
    }

    // =====================================================================
    // KWITANSI (satu lembar)
    // =====================================================================

    private function buildKwitansiCopy(Section $s): void
    {
        $inv     = $this->invoice;
        $p       = $this->project;
        $content = 21.0 - 1.4 * 2;
        $size    = 9;
        $date    = ($inv->payment_date ?? $inv->display_date)->translatedFormat('d F Y');
        $margins = ['cellMarginLeft' => 120, 'cellMarginRight' => 120, 'cellMarginTop' => 50, 'cellMarginBottom' => 50];

        // Kop: logo (berbingkai) | KWITANSI | No/Tgl
        $h = $s->addTable(['cellMarginTop' => 0, 'cellMarginBottom' => 0]);
        $h->addRow();
        $c = $h->addCell(Converter::cmToTwip($content * 0.28), array_merge($this->edges(['top', 'bottom', 'left', 'right'], 8), ['valign' => 'center']));
        $logo = public_path('images/logo-spr-short.png');
        if (is_file($logo)) {
            $w = Converter::cmToPoint(4.6);
            $c->addImage($logo, ['width' => $w, 'height' => $w / (855 / 187), 'alignment' => Jc::CENTER]);
        }
        $c = $h->addCell(Converter::cmToTwip($content * 0.32), ['valign' => 'center']);
        $this->p($c, 'KWITANSI', $this->f(18, true), Jc::CENTER);
        $this->p($c, 'RECEIPT', $this->f(11, false, true), Jc::CENTER);
        $c = $h->addCell(Converter::cmToTwip($content * 0.40), ['valign' => 'center']);
        $run = $c->addTextRun($this->p0());
        $run->addText('No. / Number : ', $this->f(8.5));
        $run->addText((string) $inv->kwitansi_number, $this->f(8.5, true));
        $run = $c->addTextRun($this->p0());
        $run->addText('Tgl. / Date : ', $this->f(8.5));
        $run->addText($date, $this->f(8.5, true));

        $s->addText('', ['size' => 2], $this->p0());

        // Isi kwitansi: bingkai luar, garis putus-putus abu-abu antar baris,
        // tanpa garis vertikal (seperti PDF).
        $labelW = Converter::cmToTwip(3.6);
        $valueW = Converter::cmToTwip($content - 3.6);
        $t = $s->addTable($margins);
        $dots = $this->edges(['bottom'], 4, '999999', 'dotted');

        $rows = [
            ['Sudah terima dari', 'Received Form', fn (Cell $c) => $this->p($c, optional($inv->payer)->client_name ?? $p->instructingClient->client_name, $this->f($size))],
            ['Banyaknya Uang', 'Amount Received', fn (Cell $c) => $this->p($c, '# ' . $this->terbilang($inv->amount) . ' #', $this->f($size, true, true))],
            ['Untuk Pembayaran', 'For payment of', function (Cell $c) use ($inv, $p, $size) {
                $this->p($c, 'Pembayaran ' . ($inv->term_description ?: 'Biaya Jasa Penilaian')
                    . ' Biaya Jasa Penilaian Properti an. ' . $inv->on_behalf_name, $this->f($size));
                $this->p($c, 'Sesuai dengan Surat Penawaran No. ' . $p->proposal_number
                    . ' tanggal ' . $p->effective_proposal_date->translatedFormat('d F Y'), $this->f($size));
            }],
        ];
        foreach ($rows as $i => [$label, $english, $fill]) {
            $top = $i === 0 ? ['top'] : [];
            $t->addRow();
            $c = $t->addCell($labelW, array_merge($this->edges(array_merge(['left'], $top), 8), $dots));
            $this->p($c, $label, $this->f($size, false, false, true));
            $this->p($c, $english, $this->f(8, false, true));
            $fill($t->addCell($valueW, array_merge($this->edges(array_merge(['right'], $top), 8), $dots)));
        }

        // Rincian Fee / PPN / Total (bingkai bawah)
        $t->addRow();
        $t->addCell($labelW, $this->edges(['left', 'bottom'], 8));
        $c = $t->addCell($valueW, $this->edges(['right', 'bottom'], 8));
        $bd = $c->addTable(['cellMarginTop' => 0, 'cellMarginBottom' => 0]);
        foreach ([
            ['Fee', $inv->net_amount, false],
            ['PPN ' . $this->ppnPercent(), $inv->ppn_amount, false],
            ['Total', $inv->amount, true],
        ] as [$label, $amount, $bold]) {
            $bd->addRow();
            $this->p($bd->addCell(Converter::cmToTwip(2.4)), $label, $this->f($size, $bold));
            $this->p($bd->addCell(Converter::cmToTwip(0.4)), ':', $this->f($size, $bold));
            $this->p($bd->addCell(Converter::cmToTwip(3.6)), 'Rp ' . $this->rp($amount), $this->f($size, $bold), Jc::END);
        }

        $s->addText('', ['size' => 2], $this->p0());

        // Nominal + cara bayar | rekening + tanda tangan — satu bingkai, tanpa garis tengah.
        $n = $s->addTable(array_merge($margins, ['cellMarginTop' => 60, 'cellMarginBottom' => 60]));
        $n->addRow();
        $c = $n->addCell(Converter::cmToTwip($content * 0.45), $this->edges(['left', 'top'], 8));
        // Nominal meniru kwitansi baku: "Rp." besar + angka di atas bidang
        // arsiran bergaris tebal (2026-09-14, feedback user).
        $rpT = $c->addTable(['cellMarginLeft' => 40, 'cellMarginRight' => 40, 'cellMarginTop' => 20, 'cellMarginBottom' => 20]);
        $rpT->addRow();
        $this->p($rpT->addCell(Converter::cmToTwip(1.4), ['valign' => 'bottom']), 'Rp.', $this->f(18, true));
        $amountCell = $rpT->addCell(Converter::cmToTwip($content * 0.45 - 1.9), array_merge(
            $this->edges(['top'], 24),
            $this->edges(['bottom'], 12),
            ['shading' => ['pattern' => 'diagStripe', 'color' => 'A6A6A6', 'fill' => 'FFFFFF'], 'valign' => 'center']
        ));
        $this->p($amountCell, $this->rp($inv->amount) . ',00', $this->f(16, true), Jc::CENTER);
        $this->p($c, '☐ CASH     ☐ CHEQUE / BG     ☐ TRANSFER', $this->f(8.5), Jc::START, 80);
        foreach (['Bank / Bank', 'Nomer / Number', 'Tanggal / Date'] as $label) {
            $this->p($c, $label . '  : ................................................', $this->f(8.5), Jc::START, 40);
        }

        $c = $n->addCell(Converter::cmToTwip($content * 0.55), $this->edges(['right', 'top'], 8));
        $this->p($c, 'Mohon ditransfer ke Rekening Kami:', $this->f($size));
        $this->p($c, 'Pemilik Rekening : ' . ($this->bank['account_name'] ?? config('kjpp.company_name')), $this->f(8.5));
        $this->p($c, 'Bank : ' . ($this->bank['bank_name'] ?? '-'), $this->f(8.5));
        if (! empty($this->bank['branch'])) {
            $this->p($c, 'Cabang : ' . $this->bank['branch'], $this->f(8.5));
        }
        $this->p($c, 'Account : ' . ($this->bank['account_number'] ?? '-'), $this->f(8.5));
        // Ruang tanda tangan yang cukup (2026-09-14, feedback user).
        for ($i = 0; $i < 5; $i++) {
            $this->p($c, '', $this->f($size));
        }
        // Nama & jabatan rata tengah di kolom rekening (2026-09-14, feedback user).
        $this->p($c, $this->signerName(), $this->f($size, true), Jc::CENTER);
        $this->p($c, $this->signerTitle(), $this->f($size, false, true), Jc::CENTER);

        // Catatan keabsahan di DALAM tabel, rata tengah, 2 baris dimulai bahasa Inggris.
        $n->addRow();
        $c = $n->addCell(Converter::cmToTwip($content), array_merge(
            $this->edges(['left', 'right', 'bottom'], 8),
            $this->edges(['top'], 4, '999999', 'dotted'),
            ['gridSpan' => 2]
        ));
        $this->p($c, 'This receipt will be cleared after Bilyet Giro/Cheque can be cleared', $this->f(7.5, false, true), Jc::CENTER);
        $this->p($c, 'Kwitansi ini baru dianggap sah, setelah pembayaran dengan Bilyet Giro/Cek tsb, dapat diuangkan', $this->f(7.5), Jc::CENTER);
    }

    // =====================================================================
    // HELPER
    // =====================================================================

    /**
     * Style garis sel untuk sisi-sisi tertentu saja (top/bottom/left/right).
     * Sisi yang tidak disebut dibiarkan tanpa garis.
     */
    private function edges(array $sides, int $size = 10, string $color = '000000', string $style = 'single'): array
    {
        $out = [];
        foreach ($sides as $side) {
            $key = ucfirst($side);
            $out["border{$key}Size"]  = $size;
            $out["border{$key}Color"] = $color;
            $out["border{$key}Style"] = $style;
        }

        return $out;
    }

    private function newDocument(float $topCm, float $bottomCm, float $sideCm): Section
    {
        // PhpWord tidak meng-escape teks secara default -> '&' pada nama klien merusak XML.
        Settings::setOutputEscapingEnabled(true);

        $this->word = new PhpWord();
        $this->word->setDefaultFontName(self::FONT);
        $this->word->setDefaultFontSize(10.5);

        return $this->word->addSection([
            'pageSizeW'    => Converter::cmToTwip(21.0),
            'pageSizeH'    => Converter::cmToTwip(29.7),
            'marginTop'    => Converter::cmToTwip($topCm),
            'marginBottom' => Converter::cmToTwip($bottomCm),
            'marginLeft'   => Converter::cmToTwip($sideCm),
            'marginRight'  => Converter::cmToTwip($sideCm),
            'footerHeight' => Converter::cmToTwip(0.8),
        ]);
    }

    private function write(string $prefix): string
    {
        $path = storage_path('app/tmp/' . $prefix . '-' . uniqid() . '.docx');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        IOFactory::createWriter($this->word, 'Word2007')->save($path);

        return $path;
    }

    private function f(float $size = 10.5, bool $bold = false, bool $italic = false, bool $underline = false, string $color = '000000'): array
    {
        return array_filter([
            'name'      => self::FONT,
            'size'      => $size,
            'bold'      => $bold,
            'italic'    => $italic,
            'underline' => $underline ? 'single' : null,
            'color'     => $color,
        ], fn ($v) => $v !== null && $v !== false);
    }

    private function p0(string $align = Jc::START, int $after = 0): array
    {
        return ['alignment' => $align, 'spaceBefore' => 0, 'spaceAfter' => $after];
    }

    private function p($container, string $text, ?array $font = null, string $align = Jc::START, int $after = 0): void
    {
        $container->addText($text, $font ?? $this->f(), $this->p0($align, $after));
    }

    /** Terbilang tanpa spasi ganda (di PDF/HTML spasi ganda tidak terlihat, di Word terlihat). */
    private function terbilang($amount): string
    {
        return trim(preg_replace('/\s+/', ' ', Terbilang::make($amount)));
    }

    private function rp($amount): string
    {
        return number_format((float) $amount, 0, ',', '.');
    }

    private function ppnPercent(): string
    {
        return rtrim(rtrim(number_format($this->invoice->ppn_rate * 100, 2, ',', ''), '0'), ',') . '%';
    }

    private function signerName(): string
    {
        return optional($this->project->signedBy)->name ?: config('kjpp.signatory.name');
    }

    private function signerTitle(): string
    {
        return optional($this->project->signedBy)->partner_status ?: config('kjpp.signatory.title');
    }
}
