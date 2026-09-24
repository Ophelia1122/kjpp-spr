{{-- Beranda Admin Produksi (juga dilihat General Admin & Administrator). --}}
@php
    $d = $produksi;
    // Release Draft Resume & kembalikan nilai adalah hak Reviewer, jadi tidak
    // pernah muncul di daftar ini (2026-09-24, feedback user).
    $actionLabel = [
        \App\Models\Project::REVIEW_RELEASED       => 'Draft Resume disetujui / banding',
        \App\Models\Project::STAGE_DRAFT_SUBMITTED => 'Konfirmasi draft laporan',
        \App\Models\Project::STAGE_DRAFT_REVIEWED  => 'Cetak buku & konfirmasi',
    ];
    $waitText = fn ($wait) => $wait === null ? '' : ($wait === 0 ? 'hari ini' : $wait . ' hari');
@endphp

{{-- Urutan kartu mengikuti alur kerja Admin Produksi: konfirmasi draft,
     cetak buku, tanda tangan, lalu jumlah laporan final yang sudah selesai
     (2026-09-24, feedback user). --}}
<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    @include('dashboard.home._tile', ['label' => 'Konfirmasi draft', 'value' => $d['confirmCount'], 'tone' => $d['confirmCount'] ? 'amber' : null, 'hint' => 'Draft laporan dari Surveyor'])
    @include('dashboard.home._tile', ['label' => 'Proses cetak buku', 'value' => $d['printCount'], 'tone' => $d['printCount'] ? 'amber' : null, 'hint' => 'Draft telah direview Reviewer'])
    @include('dashboard.home._tile', ['label' => 'Proses tanda tangan', 'value' => $d['signCount'], 'tone' => $d['signCount'] ? 'indigo' : null, 'hint' => 'Buku menunggu ditandatangani'])
    @include('dashboard.home._tile', ['label' => 'Jumlah laporan selesai', 'value' => $d['finalDoneCount'], 'tone' => null, 'hint' => 'Total laporan final'])
</div>

{{-- ---------- Perlu tindakan Admin Produksi ---------- --}}
<div class="{{ $panel }}">
    <div class="px-5 pt-5 pb-3">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Perlu tindakan Admin Produksi</h2>
        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Urut dari yang paling lama menunggu.</p>
    </div>
    @if ($d['actions']->isEmpty())
        <div class="border-t border-gray-100 px-5 py-10 text-center text-sm text-gray-400 dark:border-gray-700 dark:text-gray-500">🎉 Tidak ada yang menunggu tindakan.</div>
    @else
        <div class="hidden md:block">
            <table class="w-full text-sm">
                <thead class="border-y border-gray-100 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-5 py-2.5">Proyek</th>
                        <th class="px-4 py-2.5">Tindakan</th>
                        <th class="px-4 py-2.5">Menunggu Sejak</th>
                        <th class="px-5 py-2.5 w-[32%]">Catatan Terakhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($d['actions'] as $p)
                        @php $wait = $waitDays($p->stage_since); @endphp
                        <tr class="cursor-pointer align-top hover:bg-blue-50/40 dark:hover:bg-blue-900/20" onclick="location.href='{{ route('proposals.show', $p) }}'">
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900 dark:text-gray-100" title="{{ $p->proposal_number }}">{{ $p->proposal_number_short }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $clientOf($p) }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 whitespace-nowrap dark:bg-indigo-900/30 dark:text-indigo-300">{{ $actionLabel[$p->review_status] ?? $p->stage['label'] }}</span>
                                @if ($p->review_status === \App\Models\Project::STAGE_DRAFT_REVIEWED && ! $p->final_report_number)
                                    <span class="mt-0.5 block text-[11px] text-amber-600 dark:text-amber-400">Nomor Laporan Final belum diisi</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs whitespace-nowrap">
                                <p class="text-gray-700 dark:text-gray-300">{{ $p->stage_since?->translatedFormat('d M Y, H:i') ?? '—' }}</p>
                                <p class="{{ ($wait ?? 0) >= 3 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $waitText($wait) }}</p>
                            </td>
                            <td class="px-5 py-3">@include('dashboard.home._note', ['project' => $p])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Ponsel: kartu (2026-09-15, feedback user) --}}
        <div class="divide-y divide-gray-100 border-t border-gray-100 md:hidden dark:divide-gray-700 dark:border-gray-700">
            @foreach ($d['actions'] as $p)
                @php $wait = $waitDays($p->stage_since); @endphp
                <a href="{{ route('proposals.show', $p) }}" class="block space-y-1.5 px-4 py-3 hover:bg-blue-50/40 dark:hover:bg-blue-900/20">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $p->proposal_number_short }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $clientOf($p) }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-medium {{ ($wait ?? 0) >= 3 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $waitText($wait) }}</span>
                    </div>
                    <span class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">{{ $actionLabel[$p->review_status] ?? $p->stage['label'] }}</span>
                    @if ($p->review_status === \App\Models\Project::STAGE_DRAFT_REVIEWED && ! $p->final_report_number)
                        <p class="text-[11px] text-amber-600 dark:text-amber-400">Nomor Laporan Final belum diisi</p>
                    @endif
                    @if (isset($notes[$p->id]))
                        <div>@include('dashboard.home._note', ['project' => $p])</div>
                    @endif
                </a>
            @endforeach
        </div>

    {{-- Paginasi panel: maksimal 5 baris per halaman (2026-09-24). --}}
    @if ($d['actions']->hasPages())
        <div class="border-t border-gray-100 px-5 py-3 dark:border-gray-700">{{ $d['actions']->links() }}</div>
    @endif
    @endif
</div>

{{-- ---------- Proyek dalam SLA Laporan Final ---------- --}}
<div class="{{ $panel }}">
    <div class="px-5 pt-5 pb-3">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Proyek dalam SLA Laporan Final</h2>
        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Sejak Draft Resume disetujui sampai buku selesai dicetak. Urut dari tenggat terdekat.</p>
    </div>
    @if ($d['finalSla']->isEmpty())
        <div class="border-t border-gray-100 px-5 py-10 text-center text-sm text-gray-400 dark:border-gray-700 dark:text-gray-500">Belum ada proyek dalam SLA Laporan Final.</div>
    @else
        <div class="hidden md:block">
            <table class="w-full text-sm">
                <thead class="border-y border-gray-100 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-5 py-2.5">Proyek</th>
                        <th class="px-4 py-2.5">Tahap Sekarang</th>
                        <th class="px-4 py-2.5">Resume Disetujui</th>
                        <th class="px-5 py-2.5">Tenggat SLA Final</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($d['finalSla'] as $p)
                        <tr class="cursor-pointer align-top hover:bg-blue-50/40 dark:hover:bg-blue-900/20" onclick="location.href='{{ route('proposals.show', $p) }}'">
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900 dark:text-gray-100" title="{{ $p->proposal_number }}">{{ $p->proposal_number_short }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $clientOf($p) }}</p>
                            </td>
                            <td class="px-4 py-3">@include('dashboard.home._stage', ['project' => $p])</td>
                            <td class="px-4 py-3 text-xs text-gray-700 whitespace-nowrap dark:text-gray-300">{{ $p->review_approved_at?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                @if ($p->estimated_final_completion_date)
                                    <p class="text-xs font-medium {{ $slaTone[$p->final_sla_state] ?? '' }}">{{ $p->final_sla_label }}</p>
                                    <p class="text-[11px] text-gray-400 dark:text-gray-500">{{ $p->estimated_final_completion_date->translatedFormat('d M Y') }}</p>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">SLA Final belum diisi</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-gray-100 border-t border-gray-100 md:hidden dark:divide-gray-700 dark:border-gray-700">
            @foreach ($d['finalSla'] as $p)
                <a href="{{ route('proposals.show', $p) }}" class="block space-y-1.5 px-4 py-3 hover:bg-blue-50/40 dark:hover:bg-blue-900/20">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $p->proposal_number_short }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $clientOf($p) }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            @if ($p->estimated_final_completion_date)
                                <p class="text-xs font-medium {{ $slaTone[$p->final_sla_state] ?? '' }}">{{ $p->final_sla_label }}</p>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500">{{ $p->estimated_final_completion_date->translatedFormat('d M Y') }}</p>
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-500">SLA Final belum diisi</span>
                            @endif
                        </div>
                    </div>
                    <div>@include('dashboard.home._stage', ['project' => $p])</div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500">Resume disetujui {{ $p->review_approved_at?->translatedFormat('d M Y') ?? '—' }}</p>
                </a>
            @endforeach
        </div>

    {{-- Paginasi panel: maksimal 5 baris per halaman (2026-09-24). --}}
    @if ($d['finalSla']->hasPages())
        <div class="border-t border-gray-100 px-5 py-3 dark:border-gray-700">{{ $d['finalSla']->links() }}</div>
    @endif
    @endif
</div>
