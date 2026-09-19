{{-- Beranda Surveyor/Penilai (juga tab "Sebagai Penilai" untuk Reviewer). --}}
@php $d = $penilai; @endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    @include('dashboard.home._tile', ['label' => 'Proyek aktif', 'value' => $d['activeCount'], 'hint' => 'Belum selesai & tidak dibatalkan',
        'href' => route('dashboard', ['mine' => 1, 'focus' => 'active'])])
    @include('dashboard.home._tile', ['label' => 'Survei bulan ini', 'value' => $d['surveyMonthCount'], 'hint' => now()->translatedFormat('F Y'),
        'href' => route('dashboard', ['mine' => 1, 'focus' => 'survey_month'])])
    @include('dashboard.home._tile', ['label' => 'Proyek selesai', 'value' => $d['doneCount'], 'tone' => 'emerald', 'hint' => 'Yang Anda tangani sebagai penilai lapangan',
        'href' => route('dashboard', ['mine' => 1, 'status' => \App\Models\Project::STATUS_SELESAI])])
</div>

<div class="{{ $panel }}">
    <div class="px-5 pt-5 pb-3">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Proyek aktif saya</h2>
        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Urut dari tenggat SLA terdekat. Klik untuk membuka proyek.</p>
    </div>

    @if ($d['active']->isEmpty())
        <div class="border-t border-gray-100 px-5 py-10 text-center text-sm text-gray-400 dark:border-gray-700 dark:text-gray-500">
            Belum ada proyek aktif yang ditugaskan kepada Anda.
        </div>
    @else
        {{-- Desktop: tabel --}}
        <div class="hidden md:block">
            <table class="w-full text-sm">
                <thead class="border-y border-gray-100 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-5 py-2.5">Proyek</th>
                        <th class="px-4 py-2.5">Tahap Sekarang</th>
                        <th class="px-4 py-2.5">Tanggal Survei</th>
                        <th class="px-4 py-2.5">Tenggat SLA</th>
                        <th class="px-5 py-2.5 w-[30%]">Catatan Terakhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($d['active'] as $p)
                        <tr class="cursor-pointer align-top hover:bg-blue-50/40 dark:hover:bg-blue-900/20" onclick="location.href='{{ route('proposals.show', $p) }}'">
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900 dark:text-gray-100" title="{{ $p->proposal_number }}">{{ $p->proposal_number_short }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $clientOf($p) }}</p>
                            </td>
                            <td class="px-4 py-3">@include('dashboard.home._stage', ['project' => $p])</td>
                            <td class="px-4 py-3 text-xs text-gray-700 whitespace-nowrap dark:text-gray-300">{{ $p->survey_date?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td class="px-4 py-3">@include('dashboard.home._sla', ['project' => $p])</td>
                            <td class="px-5 py-3">@include('dashboard.home._note', ['project' => $p])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Ponsel: kartu --}}
        <div class="divide-y divide-gray-100 border-t border-gray-100 md:hidden dark:divide-gray-700 dark:border-gray-700">
            @foreach ($d['active'] as $p)
                <a href="{{ route('proposals.show', $p) }}" class="block space-y-2 px-4 py-3 hover:bg-blue-50/40 dark:hover:bg-blue-900/20">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $p->proposal_number_short }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $clientOf($p) }}</p>
                        </div>
                        <div class="shrink-0 text-right">@include('dashboard.home._sla', ['project' => $p])</div>
                    </div>
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>@include('dashboard.home._stage', ['project' => $p])</div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Survei {{ $p->survey_date?->translatedFormat('d M Y') ?? '—' }}</span>
                    </div>
                    @include('dashboard.home._note', ['project' => $p])
                </a>
            @endforeach
        </div>
    @endif
</div>
