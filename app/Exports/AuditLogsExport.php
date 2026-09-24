<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Log Aktivitas ke Excel (2026-09-24, permintaan user) — dipakai
 * sebelum log dikosongkan, dan untuk arsip berkala. Query-nya persis sama
 * dengan filter yang sedang aktif di halaman Log Aktivitas.
 */
class AuditLogsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(private $query)
    {
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['Waktu', 'Pengguna', 'Aksi', 'Keterangan', 'Catatan', 'Objek', 'ID Objek'];
    }

    /** @param \App\Models\AuditLog $log */
    public function map($log): array
    {
        return [
            $log->created_at->translatedFormat('d-m-Y H:i:s'),
            $log->user?->name ?? 'Sistem',
            $log->action,
            $log->description,
            $log->note,
            $log->subject_type ? class_basename($log->subject_type) : '',
            $log->subject_id,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
