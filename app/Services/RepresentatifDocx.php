<?php

namespace App\Services;

use App\Models\Project;
use RuntimeException;
use ZipArchive;

/**
 * Surat Representasi (Representative Letter) yang dikirim ke klien
 * bersama proposal (2026-09-21, feedback user).
 *
 * Kop dan tanda tangan diisi sendiri oleh klien, jadi dokumen dibangun dari
 * templat Word resmi (resources/templates/representatif.docx) dan hanya
 * placeholder ${...} yang diganti. Poin "inspeksi terbatas" dihapus bila
 * proyek tidak memilih inspeksi terbatas; penomoran daftar Word menyesuaikan.
 */
class RepresentatifDocx
{
    private const TEMPLATE = 'templates/representatif.docx';

    /** Penanda paragraf poin inspeksi terbatas di templat. */
    private const LIMITED_MARKER = 'Jika pelaksanaan inspeksi';

    public function __construct(private Project $project)
    {
    }

    public static function for(Project $project): self
    {
        $project->loadMissing('instructingClient', 'namedClient', 'signedBy', 'valuationObjects');

        return new self($project);
    }

    /** "02278 - Representatif_Nama Klien", aturan sama dengan nama file proposal. */
    public function fileName(): string
    {
        $no = preg_match('/^\s*(\d+)/', (string) $this->project->proposal_number, $m)
            ? str_pad(substr($m[1], -5), 5, '0', STR_PAD_LEFT)
            : '00000';
        $client = trim(preg_replace('/\s+/', ' ', preg_replace('#[\\\\/:*?"<>|]+#', ' ', $this->project->effective_client_name)));

        return $no . ' - Representatif_' . ($client ?: 'Klien');
    }

    /** Buat .docx di folder sementara dan kembalikan path-nya. */
    public function save(): string
    {
        $path = storage_path('app/tmp/representatif-' . uniqid() . '.docx');
        @mkdir(dirname($path), 0775, true);
        copy(resource_path(self::TEMPLATE), $path);

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Templat Surat Representasi tidak bisa dibuka.');
        }

        $xml = $zip->getFromName('word/document.xml');
        if (! $this->project->representative_limited) {
            $xml = $this->removeParagraph($xml, self::LIMITED_MARKER);
        }
        $xml = $this->expandLocations($xml);
        $xml = strtr($xml, array_map(
            fn ($v) => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
            $this->values()
        ));

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return $path;
    }

    private function values(): array
    {
        $p      = $this->project;
        $signer = $p->signedBy?->name ?? config('kjpp.signatory.name');
        $giver  = (string) $p->instructingClient?->client_name;

        return [
            '${tanggal}'             => $p->effective_proposal_date->translatedFormat('d F Y'),
            // Gelar (mis. MAPPI (Cert.)) sudah melekat di nama user.
            '${up}'                  => $signer,
            '${nomor}'               => $p->proposal_number,
            '${pemberi_tugas}'       => $giver,
            '${pemberi_tugas_upper}' => mb_strtoupper($giver),
            '${klien}'               => $p->effective_client_name,
        ];
    }

    /** Satu lokasi per objek, tanpa duplikat; baris ganda di textarea digabung. */
    private function locations(): array
    {
        $list = $this->project->valuationObjects
            ->map(fn ($o) => trim(preg_replace('/\s*[\r\n]+\s*/', ' ', (string) $o->location)))
            ->filter()->unique()->values()->all();

        return $list ?: [(string) $this->project->asset_address];
    }

    /**
     * Paragraf ${lokasi} digandakan: satu paragraf per lokasi, bernomor
     * "1.", "2.", ... termasuk bila hanya satu lokasi (2026-09-21, feedback user).
     */
    private function expandLocations(string $xml): string
    {
        $pos = strpos($xml, '${lokasi}');
        if ($pos === false) {
            return $xml;
        }
        [$start, $end] = $this->paragraphBounds($xml, $pos);
        $para = substr($xml, $start, $end - $start);
        $locs = $this->locations();

        $out = '';
        foreach ($locs as $i => $loc) {
            $text = ($i + 1) . '. ' . $loc;
            $out .= str_replace('${lokasi}', htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8'), $para);
        }

        return substr($xml, 0, $start) . $out . substr($xml, $end);
    }

    /** [awal, akhir] paragraf <w:p> yang memuat posisi $pos. */
    private function paragraphBounds(string $xml, int $pos): array
    {
        $before = substr($xml, 0, $pos);
        $start  = max((int) strrpos($before, '<w:p '), (int) strrpos($before, '<w:p>'));
        $end    = strpos($xml, '</w:p>', $pos) + strlen('</w:p>');

        return [$start, $end];
    }

    /** Hapus seluruh paragraf <w:p> yang memuat $marker. */
    private function removeParagraph(string $xml, string $marker): string
    {
        $pos = strpos($xml, $marker);
        if ($pos === false) {
            return $xml;
        }
        [$start, $end] = $this->paragraphBounds($xml, $pos);

        return substr($xml, 0, $start) . substr($xml, $end);
    }
}
