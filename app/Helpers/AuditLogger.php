<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * Catat 1 baris aktivitas. Dipanggil dari controller setelah aksi
     * berhasil dilakukan (bukan sebelum — supaya tidak ada log "palsu"
     * untuk aksi yang gagal validasi/dibatalkan).
     *
     * Contoh pemakaian:
     *   AuditLogger::record('proposal.created', "Membuat proposal {$project->proposal_number}", $project);
     *   AuditLogger::record('auth.login', "Login berhasil"); // tanpa subject
     */
    public static function record(string $action, string $description, ?Model $subject = null, ?string $note = null): void
    {
        AuditLog::create([
            'user_id'      => auth()->id(), // null kalau dipanggil sebelum login (mis. saat seeding)
            'action'       => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id'   => $subject?->id,
            'description'  => $description,
            // Catatan opsional per langkah alur proyek (2026-09-14).
            'note'         => filled($note) ? $note : null,
        ]);
    }
}
