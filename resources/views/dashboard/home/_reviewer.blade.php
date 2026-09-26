{{-- Beranda Reviewer: antrean review nilai & draft laporan. --}}
@php
    $d = $reviewer;
    // Label per proyek ikut istilah Non-Penilaian (2026-09-26).
    $kindBadge = fn ($p) => array_map(
        fn ($v) => is_string($v) ? $p->istilahAlur($v) : $v,
        match ($p->review_status) {
        \App\Models\Project::REVIEW_SUBMITTED => ['Nilai', 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300'],
        \App\Models\Project::REVIEW_RELEASED  => ['Draft Resume', 'bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'],
        default                                 => ['Draft Laporan', 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'],
        },
    );
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    @include('dashboard.home._tile', ['label' => 'Review nilai', 'value' => $d['valueCount'], 'tone' => $d['valueCount'] ? 'indigo' : null, 'hint' => 'Nilai menunggu persetujuan'])
    @include('dashboard.home._tile', ['label' => 'Review draft laporan', 'value' => $d['draftCount'], 'tone' => $d['draftCount'] ? 'amber' : null, 'hint' => 'Draft dikonfirmasi Admin Produksi'])
    @include('dashboard.home._tile', ['label' => 'Direview bulan ini', 'value' => $d['reviewedMonthCount'], 'tone' => 'emerald', 'hint' => 'Nilai & draft yang Anda setujui · ' . now()->translatedFormat('F Y')])
</div>

<div class="{{ $panel }}">
    <div class="px-5 pt-5 pb-3">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Antrean review</h2>
        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-400">Urut dari yang paling lama menunggu. Reviewer mana pun boleh mengambil antrean.</p>
    </div>

    @if ($d['queue']->isEmpty())
        <div class="border-t border-gray-100 px-5 py-10 text-center text-sm text-gray-400 dark:border-gray-700 dark:text-gray-400">
            🎉 Tidak ada antrean review saat ini.
        </div>
    @else
        <div class="hidden md:block">
            <table class="w-full text-sm">
                <thead class="border-y border-gray-100 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-5 py-2.5">Proyek</th>
                        <th class="px-4 py-2.5">Jenis Review</th>
                        <th class="px-4 py-2.5">Diajukan</th>
                        <th class="px-4 py-2.5">Menunggu</th>
                        <th class="px-5 py-2.5 w-[24%]">Catatan Terakhir</th>
                        <th class="px-4 py-2.5">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($d['queue'] as $p)
                        @php [$kind, $kindClass] = $kindBadge($p); $wait = $waitDays($p->stage_since); @endphp
                        <tr class="cursor-pointer align-top hover:bg-blue-50/40 dark:hover:bg-blue-900/20" onclick="location.href='{{ route('proposals.show', $p) }}'">
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900 dark:text-gray-100" title="{{ $p->proposal_number }}">{{ $p->proposal_number_short }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $clientOf($p) }}</p>
                            </td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $kindClass }}">{{ $kind }}</span></td>
                            <td class="px-4 py-3 text-xs text-gray-700 whitespace-nowrap dark:text-gray-300">{{ $p->stage_since?->translatedFormat('d M Y, H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs font-medium whitespace-nowrap {{ ($wait ?? 0) >= 3 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ $wait === null ? '—' : ($wait === 0 ? 'hari ini' : $wait . ' hari') }}
                            </td>
                            <td class="px-5 py-3">@include('dashboard.home._note', ['project' => $p])</td>
                            <td class="px-4 py-3">@include('dashboard.home._row_actions', ['project' => $p])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-gray-100 border-t border-gray-100 md:hidden dark:divide-gray-700 dark:border-gray-700">
            @foreach ($d['queue'] as $p)
                @php [$kind, $kindClass] = $kindBadge($p); $wait = $waitDays($p->stage_since); @endphp
                <a href="{{ route('proposals.show', $p) }}" class="block space-y-2 px-4 py-3 hover:bg-blue-50/40 dark:hover:bg-blue-900/20">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $p->proposal_number_short }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $clientOf($p) }}</p>
                        </div>
                        <span class="shrink-0 inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $kindClass }}">{{ $kind }}</span>
                    </div>
                    <p class="text-xs {{ ($wait ?? 0) >= 3 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-400' }}">
                        Diajukan {{ $p->stage_since?->translatedFormat('d M Y, H:i') ?? '—' }}
                        &middot; menunggu {{ $wait === null ? '—' : ($wait === 0 ? 'hari ini' : $wait . ' hari') }}
                    </p>
                    @include('dashboard.home._note', ['project' => $p])
                </a>
            @endforeach
        </div>
    {{-- Paginasi panel: maksimal 5 baris per halaman (2026-09-24). --}}
    @if ($d['queue']->hasPages())
        <div class="border-t border-gray-100 px-5 py-3 dark:border-gray-700">{{ $d['queue']->links() }}</div>
    @endif
    @endif
</div>
