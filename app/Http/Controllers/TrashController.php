<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Client;
use App\Models\Project;

/**
 * Sampah (2026-09-20). Proyek & klien yang dihapus tidak langsung hilang:
 * masuk ke sini dulu, bisa dipulihkan, dan dibersihkan otomatis setelah
 * RETENTION_DAYS hari (lihat App\Console\Commands\PurgeTrash).
 */
class TrashController extends Controller
{
    /** Isi Sampah dibuang permanen setelah sekian hari. */
    public const RETENTION_DAYS = 30;

    public function index()
    {
        return view('trash.index', [
            'projects'  => Project::onlyTrashed()->with('instructingClient')->latest('deleted_at')->limit(100)->get(),
            'clients'   => Client::onlyTrashed()->latest('deleted_at')->limit(100)->get(),
            'retention' => self::RETENTION_DAYS,
        ]);
    }

    public function restoreProject(int $id)
    {
        $project = Project::onlyTrashed()->findOrFail($id);
        $project->restore();
        AuditLogger::record('proposal.restored', "Memulihkan proposal {$project->proposal_number} dari Sampah", $project);

        return back()->with('success', "Proposal {$project->proposal_number} dipulihkan.");
    }

    public function restoreClient(int $id)
    {
        $client = Client::onlyTrashed()->findOrFail($id);
        $client->restore();
        AuditLogger::record('client.restored', "Memulihkan klien \"{$client->client_name}\" dari Sampah", $client);

        return back()->with('success', "Klien \"{$client->client_name}\" dipulihkan.");
    }

    public function forceDeleteProject(int $id)
    {
        $project = Project::onlyTrashed()->findOrFail($id);
        $number  = $project->proposal_number;
        $invoices = $project->invoices()->count();

        AuditLogger::record('proposal.purged', "Menghapus permanen proposal {$number} beserta {$invoices} invoice", $project);
        $project->forceDelete();

        return back()->with('success', "Proposal {$number} dihapus permanen.");
    }

    public function forceDeleteClient(int $id)
    {
        $client = Client::onlyTrashed()->findOrFail($id);
        $name   = $client->client_name;

        AuditLogger::record('client.purged', "Menghapus permanen klien \"{$name}\"", $client);
        $client->forceDelete();

        return back()->with('success', "Klien \"{$name}\" dihapus permanen.");
    }
}
