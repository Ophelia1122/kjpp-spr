<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\DeliveryReceipt;
use App\Models\Project;
use App\Services\DocumentNumbering;
use App\Services\TandaTerimaDocxBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Tanda Terima Pengiriman Buku (2026-09-23, feedback user).
 * Boleh dibuat Admin Produksi & General Admin; tanda terima PERTAMA menandai
 * buku sudah dikirim -> proyek Selesai (atau "Selesai - Belum Lunas").
 */
class DeliveryReceiptController extends Controller
{
    private function ensureAllowed(Project $project): void
    {
        abort_unless(
            Project::userCanActAs(auth()->user(), 'admin_or_keuangan'),
            403,
            'Hanya Admin Produksi / General Admin yang dapat membuat Tanda Terima.'
        );
        abort_if($project->isCancelled(), 403, 'Proyek batal — Tanda Terima tidak dapat dibuat.');
    }

    public function store(Request $request, Project $project)
    {
        $this->ensureAllowed($project);

        $validated = $request->validate([
            'delivery_date'       => 'required|date',
            'recipient_client_id' => 'nullable|exists:clients,id',
            'recipient_up'        => 'nullable|string|max:255',
            'note'                => 'nullable|string|max:2000',
            'documents'           => 'required|array',
            'documents.*'         => 'nullable|integer|min:0|max:999',
        ], [
            'documents.required' => 'Pilih minimal satu dokumen yang dikirim.',
        ]);

        // Hanya dokumen yang dicentang & qty >= 1; urutan mengikuti daftar baku
        // supaya nomor baris di dokumen selalu sama.
        // Disimpan sebagai LIST (bukan objek) karena MySQL mengurutkan ulang
        // kunci objek JSON, sedangkan urutan baris dokumen harus tetap.
        $documents = collect(array_keys(DeliveryReceipt::DOCUMENT_TYPES))
            ->map(fn ($key) => ['key' => $key, 'qty' => (int) ($validated['documents'][$key] ?? 0)])
            ->filter(fn ($row) => $row['qty'] > 0)
            ->values()->all();

        if (! $documents) {
            return back()->withInput()->withErrors(['documents' => 'Isi jumlah minimal satu dokumen.']);
        }

        $receipt = Cache::lock("tanda-terima-{$project->id}", 10)->block(5, function () use ($project, $validated, $documents) {
            return DB::transaction(function () use ($project, $validated, $documents) {
                $receipt = DeliveryReceipt::create([
                    'project_id'          => $project->id,
                    'number'              => app(DocumentNumbering::class)->next('tanda_terima'),
                    'delivery_date'       => $validated['delivery_date'],
                    'recipient_client_id' => $validated['recipient_client_id'] ?? null,
                    'recipient_up'        => $validated['recipient_up'] ?? null,
                    'documents'           => $documents,
                    'note'                => $validated['note'] ?? null,
                    'created_by_user_id'  => auth()->id(),
                ]);

                // Tanda terima pertama = buku sudah dikirim.
                if ($project->status === Project::STATUS_PENGIRIMAN) {
                    $project->update([
                        'review_status' => Project::STAGE_DELIVERED,
                        'delivered_at'  => now(),
                        'status'        => $project->finalStatusAfterDelivery(),
                    ]);
                }

                return $receipt;
            });
        });

        AuditLogger::record(
            'project.receipt_created',
            "Membuat Tanda Terima {$receipt->number} untuk proyek {$project->proposal_number}",
            $project
        );

        return back()->with('success', "Tanda Terima {$receipt->number} dibuat. Status proyek: {$project->fresh()->status}.");
    }

    public function destroy(Project $project, DeliveryReceipt $receipt)
    {
        $this->ensureAllowed($project);
        abort_unless($receipt->project_id === $project->id, 404);

        $number = $receipt->number;
        $receipt->delete();

        AuditLogger::record('project.receipt_deleted', "Menghapus Tanda Terima {$number} pada proyek {$project->proposal_number}", $project);

        return back()->with('success', "Tanda Terima {$number} dihapus.");
    }

    public function exportPdf(Project $project, DeliveryReceipt $receipt)
    {
        abort_unless($receipt->project_id === $project->id, 404);
        $receipt->load('recipient', 'project.instructingClient', 'project.namedClient');

        return Pdf::loadView('pdf.tanda-terima', ['receipt' => $receipt, 'project' => $project])
            ->setPaper('a4', 'portrait')
            ->download($receipt->fileName() . '.pdf');
    }

    public function exportWord(Project $project, DeliveryReceipt $receipt)
    {
        abort_unless($receipt->project_id === $project->id, 404);
        $receipt->load('recipient', 'project.instructingClient', 'project.namedClient');

        return response()
            ->download((new TandaTerimaDocxBuilder($receipt))->save(), $receipt->fileName() . '.docx')
            ->deleteFileAfterSend(true);
    }
}
