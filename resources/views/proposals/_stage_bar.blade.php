{{-- Garis perjalanan status proyek (2026-09-25, feedback user): status dulu
     hanya badge satu kata, sehingga posisi proyek dalam alur tidak terbaca
     sekali lihat. Butuh $project. --}}
@php
    use App\Models\Project;

    $urutan = [
        Project::STATUS_DRAFT            => 'Draft',
        Project::STATUS_WAITING_APPROVAL => 'Menunggu Klien',
        Project::STATUS_DP_INVOICING     => 'Invoice DP',
        Project::STATUS_IN_PROGRESS      => 'Pengerjaan',
        Project::STATUS_FINALISASI       => 'Finalisasi',
        Project::STATUS_TANDA_TANGAN     => 'Tanda Tangan',
        Project::STATUS_PENGIRIMAN       => 'Pengiriman',
        Project::STATUS_SELESAI          => 'Selesai',
    ];

    // "Selesai - Belum Lunas" dan "Selesai" menempati kotak terakhir yang sama.
    $statusSekarang = $project->status === Project::STATUS_SELESAI_BELUM_LUNAS
        ? Project::STATUS_SELESAI
        : $project->status;

    $posisi = array_search($statusSekarang, array_keys($urutan), true);
@endphp

@if (! $project->isCancelled() && $posisi !== false)
    <div class="flex items-center gap-1 overflow-x-auto rounded-lg border border-gray-200 bg-white px-3 py-2 shadow-sm dark:border-gray-700 dark:bg-gray-800"
         role="list" aria-label="Tahap proyek">
        @foreach ($urutan as $status => $label)
            @php
                $i     = $loop->index;
                $lewat = $i < $posisi;
                $kini  = $i === $posisi;
            @endphp
            <div role="listitem" class="flex shrink-0 items-center gap-1">
                <span title="{{ $status }}"
                      class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-medium whitespace-nowrap
                             {{ $kini
                                ? 'bg-blue-600 text-white'
                                : ($lewat
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                    : 'text-gray-400 dark:text-gray-500') }}">
                    @if ($lewat)
                        <svg aria-hidden="true" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                    @endif
                    {{ $label }}
                </span>
                @unless ($loop->last)
                    <span aria-hidden="true" class="h-px w-3 {{ $lewat ? 'bg-emerald-300 dark:bg-emerald-800' : 'bg-gray-200 dark:bg-gray-700' }}"></span>
                @endunless
            </div>
        @endforeach

        @if ($project->status === Project::STATUS_SELESAI_BELUM_LUNAS)
            <span class="ml-1 shrink-0 rounded-full bg-amber-50 px-2 py-1 text-[11px] font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">Belum lunas</span>
        @endif
    </div>
@endif
