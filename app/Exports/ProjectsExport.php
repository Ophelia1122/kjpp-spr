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
            'Total Invoice DP',
            'Status Invoice DP',
            'Total Invoice Pelunasan',
            'Status Invoice Pelunasan',
            'Nomor Laporan Resmi',
            'Tanggal Dibuat',
        ];
    }

    /**
     * @param Project $project
     */
    public function map($project): array
    {
        $dpInvoice    = $project->invoices->firstWhere('invoice_type', 'DP');
        $finalInvoice = $project->invoices->firstWhere('invoice_type', 'Pelunasan');

        return [
            $project->proposal_number,
            $project->status,
            $project->proposal_purpose,
            $project->instructingClient->client_name ?? '-',
            $project->intendedUsers->pluck('client_name')->implode(', '),
            $project->asset_type,
            $project->asset_address,
            $project->report_style,
            $project->sla_draft_days ?? '-',
            $project->sla_final_days ?? '-',
            (float) $project->service_fee,
            $project->assigned_appraiser ?? '-',
            $project->survey_date?->format('d-m-Y') ?? '-',
            $project->estimated_completion_date_formatted ?? '-',
            $dpInvoice ? (float) $dpInvoice->amount : 0,
            $dpInvoice->status ?? '-',
            $finalInvoice ? (float) $finalInvoice->amount : 0,
            $finalInvoice->status ?? '-',
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
