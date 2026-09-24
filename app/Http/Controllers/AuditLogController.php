<?php

namespace App\Http\Controllers;

use App\Exports\AuditLogsExport;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class AuditLogController extends Controller
{
    /** Filter daftar log, dipakai bersama oleh index() dan export(). */
    private function filtered(Request $request)
    {
        $query = AuditLog::with('user')->latest('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        // Zona waktu aplikasi & database sudah WIB (2026-09-14), jadi tanggal
        // filter langsung dibandingkan tanpa konversi.
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query;
    }

    public function index(Request $request)
    {
        // Dikunci 25 baris (2026-09-20, feedback user) — pilihan 15/25 dihapus.
        $logs  = $this->filtered($request)->paginate(25)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('audit.index', compact('logs', 'users'));
    }

    /** Unduh log sesuai filter yang sedang aktif ke Excel (2026-09-24). */
    public function export(Request $request)
    {
        return Excel::download(
            new AuditLogsExport($this->filtered($request)),
            'Log Aktivitas ' . now()->format('Y-m-d_Hi') . '.xlsx'
        );
    }

    /**
     * Hapus SELURUH log aktivitas. Hanya Administrator, dan wajib mengetik
     * ulang kata sandinya sendiri (2026-09-24, permintaan user). Tindakan ini
     * tidak bisa dibatalkan, jadi jumlah baris yang dihapus dicatat sebagai
     * satu baris log baru supaya jejaknya tidak hilang sama sekali.
     */
    public function clear(Request $request)
    {
        abort_unless($request->user()->isAdministrator(), 403);

        $request->validate(
            ['password' => 'required|string'],
            ['password.required' => 'Masukkan kata sandi Anda untuk mengosongkan log.']
        );

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            return back()->withErrors(['password' => 'Kata sandi salah. Log tidak dikosongkan.']);
        }

        $jumlah = AuditLog::count();
        AuditLog::query()->delete();

        \App\Helpers\AuditLogger::record(
            'audit.cleared',
            'Mengosongkan log aktivitas (' . number_format($jumlah, 0, ',', '.') . ' baris dihapus)'
        );

        return redirect()->route('audit.index')
            ->with('success', 'Log aktivitas dikosongkan — ' . number_format($jumlah, 0, ',', '.') . ' baris dihapus.');
    }
}
