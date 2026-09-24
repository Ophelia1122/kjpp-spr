<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithChunkReading
{
    /**
     * @param array $filters Dari modal Export Excel di List Project (2026-09-14):
     *                       date_field + from/to (rentang waktu), status, purpose,
     *                       appraiser, q, mine.
     */
    public function __construct(private array $filters = [])
    {
    }

    /**
     * Dibaca per potongan 500 baris (2026-09-20, hasil audit skala): sebelumnya
     * seluruh hasil ditarik ke memori sekaligus, sehingga export rentang
     * bertahun-tahun bisa menghabiskan memori PHP di NAS.
     */
    public function chunkSize(): int
    {
        return 500;
    }

    public function query()
    {
        $f     = $this->filters;
        $query = Project::with(['instructingClient', 'namedClient', 'intendedUsers', 'invoices']);

        $dateField = in_array($f['date_field'] ?? null, ['proposal_date', 'created_at', 'survey_date'], true)
            ? $f['date_field']
            : 'proposal_date';
        if (! empty($f['from'])) {
            $query->whereDate($dateField, '>=', $f['from']);
        }
        if (! empty($f['to'])) {
            $query->whereDate($dateField, '<=', $f['to']);
        }

        if (! empty($f['status'])) {
            $query->where('status', $f['status']);
        }
        if (! empty($f['purpose'])) {
            $query->where('proposal_purpose', $f['purpose']);
        }
        if (! empty($f['appraiser'])) {
            $query->forAppraiser($f['appraiser']);
        }
        if (! empty($f['mine'])) {
            $query->forAppraiser(auth()->id());
        }

        if (! empty($f['q'])) {
            $keyword = $f['q'];
            $query->where(function ($q) use ($keyword) {
                $q->where('proposal_number', 'like', "%{$keyword}%")
                  ->orWhere('client_name', 'like', "%{$keyword}%")
                  ->orWhereHas('instructingClient', fn ($sub) => $sub->where('client_name', 'like', "%{$keyword}%"))
                  ->orWhereHas('namedClient', fn ($sub) => $sub->where('client_name', 'like', "%{$keyword}%"))
                  ->orWhereHas('intendedUsers', fn ($sub) => $sub->where('client_name', 'like', "%{$keyword}%"));
            });
        }

        return $query->orderBy($dateField)->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'No. Proposal',
            'Tanggal Proposal',
            'Status',
            'Tujuan Penilaian',
            'Pemberi Tugas',
            'Nama Klien',
            'Pengguna Laporan',
            'Marketing',
            'Jenis Objek',
            'Alamat Objek',
            'Jenis Laporan',
            'SLA Draft (Hari Kerja)',
            'SLA Final (Hari Kerja)',
            'Fee Jasa (Rp)',
            'Penilai Lapangan',
            'Tanggal Survei',
            'Estimasi Selesai',
            'Jumlah Invoice Diterbitkan',
            'Total Ditagihkan (Rp)',
            'Total Dibayar (Rp)',
            'Sisa Pelunasan (Rp)',
            'Status Pembayaran',
            'Nomor Laporan Final',
            'Tanggal Dibuat',
        ];
    }

    /**
     * @param Project $project
     */
    public function map($project): array
    {
        $totalBilled = (float) $project->invoices->sum('amount');
        $statusPembayaran = $project->invoices->isEmpty()
            ? 'Belum Ada Invoice'
            : ($project->is_fully_paid ? 'Lunas' : 'Belum Lunas');

        return [
            $project->proposal_number,
            ($project->proposal_date ?? $project->created_at)->format('d-m-Y'),
            $project->status,
            $project->proposal_purpose,
            $project->instructingClient->client_name ?? '-',
            $project->effective_client_name ?: '-',
            $project->intendedUsers->pluck('client_name')->implode(', '),
            $project->marketing_name ?: '-',
            $project->asset_type,
            $project->asset_address,
            $project->report_style,
            $project->sla_draft_days ?? '-',
            $project->sla_final_days ?? '-',
            (float) $project->total_fee,
            $project->assigned_appraiser ?? '-',
            $project->survey_date?->format('d-m-Y') ?? '-',
            $project->estimated_completion_date_formatted ?? '-',
            $project->invoices->count(),
            $totalBilled,
            (float) $project->total_paid,
            (float) $project->remaining_balance,
            $statusPembayaran,
            $project->final_report_number ?? '-',
            $project->created_at->format('d-m-Y'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
