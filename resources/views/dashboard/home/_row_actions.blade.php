{{-- Tombol alur kerja langsung di baris tabel Beranda (2026-09-24, feedback
     user): dulu tiap keputusan butuh buka proyek, cari tombol, lalu kembali.
     Hanya langkah MAJU yang tampil di sini; langkah yang wajib memakai catatan
     (mengembalikan ke Surveyor, banding) tetap dikerjakan di halaman proyek
     supaya alasannya terisi. Butuh $project. --}}
@php
    $langkah = collect($project->availableWorkflowSteps(auth()->user()))
        ->reject(fn ($step) => $step['note'] === 'required')
        ->take(2);
@endphp

@if ($langkah->isNotEmpty())
    <div class="flex flex-wrap items-center gap-1.5" onclick="event.stopPropagation()">
        @foreach ($langkah as $key => $step)
            <form action="{{ route('projects.workflow', [$project, $key]) }}" method="POST"
                  data-confirm="{{ $step['title'] }} untuk proyek {{ $project->proposal_number }}?">
                @csrf
                <button type="submit" title="{{ $step['tip'] }}"
                        class="inline-flex items-center gap-1 rounded-md border border-emerald-300 px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-400 dark:hover:bg-emerald-900/30">
                    <svg aria-hidden="true" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                    {{ $step['button'] }}
                </button>
            </form>
        @endforeach
    </div>
@endif
