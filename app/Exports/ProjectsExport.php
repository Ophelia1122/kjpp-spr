<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    /**
     * @param array $filters Filter yang sama dengan yang dipakai di DashboardController@index
     *                       (status, q) supaya hasil export = apa yang sedang dilihat user.
     */
    public function __construct(private array $filters = [])
    {
    }

    public function collection()
    {
        $query = Project::with(['instructingClient', 'intendedUsers', 'invoices'])->latest();

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['q'])) {
            $keyword = $this->filters['q'];
            $query->where(function ($q) use ($keyword) {
                $q->where('proposal_number', 'like', "%{$keyword}%")
                  ->orWhereHas('instructingClient', function ($sub) use ($keyword) {
                      $sub->where('client_name', 'like', "%{$keyword}%");
                  });
            });
        }
        if (!empty($this->filters['mine'])) {
        $query->where('assigned_appraiser_id', auth()->id());
        }
        return $query->get();
    }

    public function headings(): array
    {
        return [
            'No. Proposal',
            'Tanggal Proposal',
            'Status',
            'Jenis Proposal',
            'Pemberi Tugas',
            'Pengguna Laporan',
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
            'Sisa Tagihan (Rp)',
            'Status Pembayaran',
            'Nomor Laporan Resmi',
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
            $project->intendedUsers->pluck('client_name')->implode(', '),
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
