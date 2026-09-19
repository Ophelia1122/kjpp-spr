<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Invoice;

/**
 * Penomoran Invoice & Kwitansi — satu tempat untuk InvoiceController,
 * Dashboard Pembayaran, dan seeder.
 *
 * Format: "<urut 3 digit>/<suffix>/<romawi bulan>/<tahun>", urutan reset
 * tiap tahun. Nomor berikutnya = MAX(nomor tertinggi yang sudah terbit di
 * aplikasi tahun ini, nomor terakhir yang diisi manual) + 1.
 *
 * "Nomor terakhir manual" (2026-09-14, feedback user): aplikasi mulai dipakai
 * di tengah bulan, sementara nomor-nomor sebelumnya terbit di luar aplikasi.
 * Disimpan sebagai "<tahun>:<angka>" dan otomatis diabaikan saat tahun
 * berganti, jadi urutan tahun baru kembali mulai dari 001.
 */
class DocumentNumbering
{
    private const ROMAN_MONTHS = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    public const TYPES = [
        'invoice' => [
            'label'  => 'Invoice',
            'column' => 'invoice_number',
            'suffix' => 'KJPPSPR-INV-JKT',
            'date'   => 'created_at',
        ],
        // Kwitansi Bayar Nanti bisa terbit sebelum dibayar (payment_date masih
        // kosong), jadi tahun diambil dari tanggal bayar -> tanggal invoice ->
        // tanggal dibuat. Tanpa ini nomornya tidak terhitung dan bisa dobel.
        'kwitansi' => [
            'label'  => 'Kwitansi',
            'column' => 'kwitansi_number',
            'suffix' => 'KJPPSPR-KEU-JK',
            'date'   => 'COALESCE(payment_date, invoice_date, created_at)',
        ],
    ];

    /** Hasil hitung per instance — summary() memanggil tiap fungsi berulang (2026-09-15). */
    private array $issuedCache = [];
    private array $manualCache = [];

    /** Nomor urut tertinggi yang sudah terbit di aplikasi tahun ini. */
    public function issuedMax(string $type): int
    {
        if (isset($this->issuedCache[$type])) {
            return $this->issuedCache[$type];
        }
        $cfg = self::TYPES[$type];

        // Ambil nomor TERBESAR (bukan baris id terakhir) — kwitansi bisa terbit
        // tidak berurutan dengan id invoice-nya.
        return $this->issuedCache[$type] = (int) Invoice::whereRaw("YEAR({$cfg['date']}) = ?", [now()->year])
            ->whereNotNull($cfg['column'])
            ->pluck($cfg['column'])
            ->map(fn ($n) => (int) explode('/', $n)[0])
            ->max();
    }

    /** Nomor terakhir yang diisi manual untuk tahun ini (0 = belum diisi). */
    public function manualLast(string $type): int
    {
        if (isset($this->manualCache[$type])) {
            return $this->manualCache[$type];
        }
        $raw = AppSetting::getValue($this->settingKey($type));
        if (! $raw || ! str_contains($raw, ':')) {
            return $this->manualCache[$type] = 0;
        }

        [$year, $number] = explode(':', $raw, 2);

        return $this->manualCache[$type] = (int) $year === now()->year ? (int) $number : 0;
    }

    public function setManualLast(string $type, int $number): void
    {
        AppSetting::putValue($this->settingKey($type), now()->year . ':' . $number);
        unset($this->manualCache[$type]);
    }

    public function lastNumber(string $type): int
    {
        return max($this->issuedMax($type), $this->manualLast($type));
    }

    public function next(string $type): string
    {
        return $this->format($type, $this->lastNumber($type) + 1);
    }

    public function format(string $type, int $sequence): string
    {
        return sprintf('%03d/%s', $sequence, $this->tail($type));
    }

    /** Bagian setelah nomor urut, mis. "KJPPSPR-INV-JKT/IX/2026". */
    public function tail(string $type): string
    {
        return self::TYPES[$type]['suffix'] . '/' . self::ROMAN_MONTHS[now()->month - 1] . '/' . now()->year;
    }

    /** Data untuk modal pengaturan di Dashboard Pembayaran. */
    public function summary(): array
    {
        $out = [];
        foreach (array_keys(self::TYPES) as $type) {
            $out[$type] = [
                'label'  => self::TYPES[$type]['label'],
                'issued' => $this->issuedMax($type),
                'manual' => $this->manualLast($type),
                'last'   => $this->lastNumber($type),
                'next'   => $this->next($type),
                'tail'   => $this->tail($type),
            ];
        }

        return $out;
    }

    private function settingKey(string $type): string
    {
        return "numbering.{$type}.last";
    }
}
