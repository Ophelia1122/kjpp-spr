<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export SPJ Surveyor (2026-09-25, permintaan user). Dibuat SATU BARIS PER
 * OBJEK supaya gampang dibaca dan langsung bisa dijumlah di Excel: nama
 * penilai diulang di tiap barisnya, jadi kolomnya bisa difilter & di-pivot
 * tanpa merapikan apa pun dulu. Baris ringkasan per penilai ditaruh di akhir.
 */
class SpjSurveyorExport implements FromArray, WithHeadings, WithStyles, WithTitle, WithEvents, ShouldAutoSize
{
    public function __construct(
        private $rows,               // hasil pengelompokan per penilai
        private $from,
        private $to,
    ) {
    }

    public function title(): string
    {
        return 'SPJ Surveyor';
    }

    public function headings(): array
    {
        return [
            ['SPJ SURVEYOR — KJPP SUGIANTO PRASODJO DAN REKAN'],
            ['Periode survei: ' . $this->from->translatedFormat('d F Y') . ' s/d ' . $this->to->translatedFormat('d F Y')],
            [],
            ['Penilai', 'No. Proposal', 'Klien', 'Tanggal Survei', 'No.', 'Objek', 'Lokasi'],
        ];
    }

    public function array(): array
    {
        $baris = [];

        foreach ($this->rows as $row) {
            $nama = $row['user']->name;

            foreach ($row['projects'] as $proyek) {
                $objek = $row['objectsPer'][$proyek->id] ?? collect();

                foreach ($objek->values() as $i => $o) {
                    $baris[] = [
                        $nama,
                        $proyek->proposal_number,
                        $proyek->effective_client_name ?: '-',
                        $proyek->survey_date?->translatedFormat('d M Y') ?: '-',
                        $i + 1,
                        $o->short_label,
                        $o->location ?: '(lokasi belum diisi)',
                    ];
                }
            }
        }

        // Ringkasan per penilai: jumlah proyek & objek, ditaruh setelah satu
        // baris kosong supaya tidak tercampur dengan rinciannya.
        $baris[] = [];
        $baris[] = ['REKAP PER PENILAI', '', '', '', '', '', ''];
        $baris[] = ['Penilai', 'Jumlah Proyek', 'Jumlah Objek', '', '', '', ''];

        foreach ($this->rows as $row) {
            $baris[] = [$row['user']->name, $row['projects']->count(), $row['objects'], '', '', '', ''];
        }

        return $baris;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 13]],
            2 => ['font' => ['italic' => true, 'color' => ['rgb' => '5B6478']]],
            4 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet  = $event->sheet->getDelegate();
                $tinggi = $sheet->getHighestRow();

                // Judul kolom: latar biru tua + baris dibekukan supaya tetap
                // terlihat saat digulir.
                $sheet->getStyle('A4:G4')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('001F60');
                $sheet->freezePane('A5');
                $sheet->setAutoFilter('A4:G4');

                // Garis tipis untuk seluruh tabel rincian.
                $sheet->getStyle('A4:G' . $tinggi)->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                    ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('C9D2E4'));

                $sheet->getStyle('E:E')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('G4:G' . $tinggi)->getAlignment()->setWrapText(true);
            },
        ];
    }
}
