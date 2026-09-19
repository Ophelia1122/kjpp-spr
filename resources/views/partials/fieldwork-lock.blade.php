{{-- Keterangan kartu Penilai Lapangan / Surat Tugas yang terkunci (2026-09-15).
     Lihat Project::canPrepareFieldwork(). Param: $project --}}
<p class="flex items-start gap-2 rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-500 dark:bg-gray-900/40 dark:text-gray-400">
    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
    <span>
        @if ($project->status === \App\Models\Project::STATUS_SELESAI || $project->isCancelled())
            Proyek sudah {{ $project->status }} — data tidak dapat diubah.
        @elseif ($project->isPaymentDeferred())
            Terkunci sampai pekerjaan dimulai. Skema Bayar Nanti: tekan tombol <b>Mulai Tanpa DP</b> dulu.
        @else
            Terkunci sampai pekerjaan dimulai. Skema DP di awal: bisa diisi setelah invoice DP ditandai <b>Dibayar</b>.
        @endif
    </span>
</p>
