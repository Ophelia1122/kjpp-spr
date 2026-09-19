<?php

namespace App\Helpers;

/**
 * Pemenggalan alamat panjang untuk dokumen cetak — aturan sama dengan alamat
 * Pemberi Tugas di proposal (ProposalDocxBuilder::addressLines): baris baru
 * HANYA setelah koma, tiap baris dijaga <= $maxChars karakter. Segmen tanpa
 * koma yang lebih panjang dari batas tetap satu baris utuh.
 */
class AddressFormatter
{
    /**
     * ±8 cm pada font 11pt Arial Narrow (proposal: 55 karakter ≈ 7 cm).
     * Dipakai "Kepada Yth" Surat Tugas (2026-09-14, feedback user).
     */
    public const SURAT_TUGAS_LINE_CHARS = 63;

    /** @return string[] baris-baris alamat; array kosong bila alamat kosong. */
    public static function lines(?string $address, int $maxChars = self::SURAT_TUGAS_LINE_CHARS): array
    {
        $address = trim(preg_replace('/\s+/', ' ', (string) $address));
        if ($address === '') {
            return [];
        }

        $segments = array_values(array_filter(
            array_map('trim', preg_split('/,\s*/', $address)),
            fn ($s) => $s !== ''
        ));

        $lines = [];
        $current = '';
        $count = count($segments);
        foreach ($segments as $i => $segment) {
            $piece = $segment . ($i < $count - 1 ? ',' : '');
            if ($current === '') {
                $current = $piece;
            } elseif (mb_strlen($current . ' ' . $piece) <= $maxChars) {
                $current .= ' ' . $piece;
            } else {
                $lines[] = $current;
                $current = $piece;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: [$address];
    }
}
