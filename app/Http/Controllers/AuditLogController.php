<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
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

        $perPage = in_array((int) $request->get('per_page'), [15, 25], true) ? (int) $request->get('per_page') : 25;
        $logs = $query->paginate($perPage)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('audit.index', compact('logs', 'users'));
    }
}
