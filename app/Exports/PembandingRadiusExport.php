<?php

namespace App\Exports;

use App\Models\LandValuePoint;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Tabel pembanding dalam radius, disusun seperti kertas kerja kantor
 * (2026-09-27, permintaan user): kriteria di kolom kiri, tiap pembanding jadi
 * satu kolom ke kanan — Data 1, Data 2, dan seterusnya.
 */
class PembandingRadiusExport implements FromArray, WithTitle, WithEvents
{
    /** Baris kertas kerja, urut dari atas. */
    private const BARIS = [
        'Jenis Properti',
        'Nama Sumber Data',
        'Nomor Telfon',
        'Alamat',
        'Nilai Penawaran',
        'Luas Tanah (m²)',
        'Luas Bangunan (m²)',
        'Waktu Expose Pasar',
        'Waktu Expose Likuidasi',
        'Titik Koordinat',
    ];

    public function __construct(
        private Collection $pembanding,   // [['titik' => LandValuePoint, 'jarak' => float], ...]
        private array $titikDicari,
        private string $cakupan,
    ) {
    }

    public function title(): string
    {
        return 'Data Pembanding';
    }

    public function array(): array
    {
        $rp = fn ($v) => $v ? 'Rp' . number_format((float) $v, 0, ',', '.') : '-';

        $kepala = ['Kriteria / Informasi'];
        $isi    = array_fill_keys(self::BARIS, []);

        foreach ($this->pembanding->values() as $i => $b) {
            /** @var LandValuePoint $p */
            $p = $b['titik'];
            $kepala[] = 'Data ' . ($i + 1);

            $isi['Jenis Properti'][]         = $p->property_type ?: $p->kelas_label;
            $isi['Nama Sumber Data'][]       = $p->source_name ?: '-';
            $isi['Nomor Telfon'][]           = $p->source_phone ?: '-';
            $isi['Alamat'][]                 = trim(implode(', ', array_filter([
                $p->address, $p->village, $p->district, $p->city, $p->province,
            ]))) ?: '-';
            $isi['Nilai Penawaran'][]        = $p->offer_total ? $rp($p->offer_total) : $rp($p->land_rate) . ' /m²';
            $isi['Luas Tanah (m²)'][]        = $p->land_area ?: '-';
            $isi['Luas Bangunan (m²)'][]     = $p->building_area ?: '-';
            // Dua kolom ini belum ada di berkas sumber; dibiarkan kosong untuk
            // diisi penilai, bukan ditebak.
            $isi['Waktu Expose Pasar'][]     = '';
            $isi['Waktu Expose Likuidasi'][] = '';
            $isi['Titik Koordinat'][]        = $p->latitude . ', ' . $p->longitude;
        }

        $tabel = [
            ['Data pembanding dalam radius ' . $this->cakupan . ' dari titik ' .
                $this->titikDicari[0] . ', ' . $this->titikDicari[1]],
            ['Diambil ' . now()->translatedFormat('d F Y H:i') . ' · ' . $this->pembanding->count() . ' data'],
            [],
            $kepala,
        ];

        foreach (self::BARIS as $baris) {
            $tabel[] = array_merge([$baris], $isi[$baris]);
        }

        return $tabel;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet  = $event->sheet->getDelegate();
                $kolom  = $this->pembanding->count() + 1;
                $akhir  = 4 + count(self::BARIS);
                $huruf  = $sheet->getCellByColumnAndRow($kolom, 1)->getColumn();

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);

                // Baris kepala tabel.
                $sheet->getStyle("A4:{$huruf}4")->getFont()->setBold(true);
                $sheet->getStyle("A4:{$huruf}4")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8ECF5');

                // Kolom kriteria tebal.
                $sheet->getStyle('A5:A' . $akhir)->getFont()->setBold(true);

                $sheet->getStyle("A4:{$huruf}{$akhir}")->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle("A4:{$huruf}{$akhir}")->getAlignment()
                    ->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);

                // Lebar dipatok sempit dan teksnya dilipat: ukuran otomatis
                // membuat kolom alamat selebar layar (2026-09-27, feedback user).
                $sheet->getColumnDimension('A')->setWidth(22);

                for ($i = 2; $i <= $kolom; $i++) {
                    $sheet->getColumnDimensionByColumn($i)->setWidth(20);
                }

                // Baris judul & alamat dibiarkan menyesuaikan isi yang dilipat.
                for ($r = 5; $r <= $akhir; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(-1);
                }

                $sheet->freezePane('B5');
            },
        ];
    }
}
