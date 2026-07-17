<?php

namespace App\Http\Controllers;

use App\Exports\ProjectsExport;
use App\Models\Project;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    /**
     * Halaman utama (taskbar/menu masuk dari sini). Menampilkan seluruh
     * proyek dalam bentuk tabel, dengan filter status & pencarian ringan
     * di sisi query (bukan JS DataTables) supaya tetap ringan.
     */
    public function index(Request $request)
    {
        $query = Project::with('instructingClient')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('proposal_number', 'like', "%{$keyword}%")
                  ->orWhere('property_owner_name', 'like', "%{$keyword}%")
                  ->orWhereHas('instructingClient', function ($sub) use ($keyword) {
                      $sub->where('client_name', 'like', "%{$keyword}%");
                  });
            });
        }

        $projects = $query->paginate(15)->withQueryString();

        // Dipakai untuk filter dropdown status di view
        $statusOptions = [
            Project::STATUS_DRAFT,
            Project::STATUS_WAITING_APPROVAL,
            Project::STATUS_DP_INVOICING,
            Project::STATUS_IN_PROGRESS,
            Project::STATUS_PELUNASAN,
            Project::STATUS_SELESAI,
        ];

        return view('dashboard.index', compact('projects', 'statusOptions'));
    }

    /**
     * Export seluruh data proyek (sesuai filter yang sedang aktif di
     * dashboard) menjadi file Excel (.xlsx).
     * Membutuhkan package: composer require maatwebsite/excel
     */
    public function exportExcel(Request $request)
    {
        $filename = 'Daftar-Proyek-KJPP-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new ProjectsExport($request->all()), $filename);
    }
}
