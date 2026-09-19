{{-- Beranda keuangan (General Admin & Administrator) — 2026-09-15, feedback user. --}}
@php
    $d = $keuangan;
    $dueTone = fn ($days) => $days < 0 ? 'text-rose-600 dark:text-rose-400' : ($days === 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400');
    $dueText = fn ($days) => $days < 0 ? 'lewat ' . abs($days) . ' hari' : ($days === 0 ? 'hari ini' : $days . ' hari lagi');
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    @include('dashboard.home._tile', ['label' => 'Invoice tertunggak', 'value' => $d['overdue']->count(), 'tone' => $d['overdue']->count() ? 'rose' : null,
        'sub' => $rp($d['overdueSum']), 'hint' => 'Belum dibayar lebih dari ' . $d['overdueDays'] . ' hari sejak terbit',
        'href' => route('dashboard.pembayaran', ['status' => 'overdue'])])
    @include('dashboard.home._tile', ['label' => 'Selesai, belum lunas', 'value' => $d['unpaidDone']->count(), 'tone' => $d['unpaidDone']->count() ? 'amber' : null,
        'sub' => $rp($d['unpaidDoneSum']), 'hint' => 'Sisa tagihan pekerjaan yang sudah selesai'])
</div>

{{-- ---------- Pekerjaan selesai, belum lunas ---------- --}}
<div class="{{ $panel }}">
    <div class="px-5 pt-5 pb-3">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Pekerjaan selesai, belum lunas</h2>
        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Tenggat penagihan {{ $d['dueDays'] }} hari setelah buku selesai dicetak. Urut dari tenggat terdekat.</p>
    </div>
    @if ($d['unpaidDone']->isEmpty())
        <div class="border-t border-gray-100 px-5 py-10 text-center text-sm text-gray-400 dark:border-gray-700 dark:text-gray-500">🎉 Semua pekerjaan selesai sudah lunas.</div>
    @else
        <div class="hidden md:block">
            <table class="w-full text-sm">
                <thead class="border-y border-gray-100 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-5 py-2.5">Proyek</th>
                        <th class="px-4 py-2.5">Selesai</th>
                        <th class="px-4 py-2.5 text-right">Sisa Tagihan</th>
                        <th class="px-4 py-2.5 text-right">Belum Ditagih</th>
                        <th class="px-5 py-2.5">Tenggat Penagihan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($d['unpaidDone'] as $row)
                        @php $p = $row['project']; @endphp
                        <tr class="cursor-pointer align-top hover:bg-blue-50/40 dark:hover:bg-blue-900/20" onclick="location.href='{{ route('proposals.show', $p) }}'">
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900 dark:text-gray-100" title="{{ $p->proposal_number }}">{{ $p->proposal_number_short }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $clientOf($p) }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-700 whitespace-nowrap dark:text-gray-300">{{ $row['doneAt']->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums whitespace-nowrap text-gray-900 dark:text-gray-100">{{ $rp($p->remaining_balance) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap {{ $row['notBilled'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $row['notBilled'] > 0 ? $rp($row['notBilled']) : '—' }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <p class="text-xs font-medium {{ $dueTone($row['dueDays']) }}">{{ $dueText($row['dueDays']) }}</p>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500">{{ $row['due']->translatedFormat('d M Y') }}</p>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Ponsel: kartu (2026-09-15, feedback user) --}}
        <div class="divide-y divide-gray-100 border-t border-gray-100 md:hidden dark:divide-gray-700 dark:border-gray-700">
            @foreach ($d['unpaidDone'] as $row)
                @php $p = $row['project']; @endphp
                <a href="{{ route('proposals.show', $p) }}" class="block space-y-1.5 px-4 py-3 hover:bg-blue-50/40 dark:hover:bg-blue-900/20">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $p->proposal_number_short }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $clientOf($p) }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-[11px] text-gray-400 dark:text-gray-500">Sisa tagihan</p>
                            <p class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $rp($p->remaining_balance) }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Selesai {{ $row['doneAt']->translatedFormat('d M Y') }}
                            @if ($row['notBilled'] > 0)
                                &middot; <span class="text-amber-600 dark:text-amber-400">belum ditagih {{ $rp($row['notBilled']) }}</span>
                            @endif
                        </span>
                        <span class="font-medium {{ $dueTone($row['dueDays']) }}">Tagih: {{ $dueText($row['dueDays']) }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>

{{-- ---------- Invoice tertunggak ---------- --}}
<div class="{{ $panel }}">
    <div class="px-5 pt-5 pb-3 flex items-start justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Invoice tertunggak</h2>
            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Belum dibayar lebih dari {{ $d['overdueDays'] }} hari sejak terbit.</p>
        </div>
        <a href="{{ route('dashboard.pembayaran') }}" class="text-xs font-medium text-blue-600 whitespace-nowrap hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Dashboard Pembayaran →</a>
    </div>
    @if ($d['overdue']->isEmpty())
        <div class="border-t border-gray-100 px-5 py-10 text-center text-sm text-gray-400 dark:border-gray-700 dark:text-gray-500">🎉 Tidak ada invoice tertunggak.</div>
    @else
        <div class="hidden md:block">
            <table class="w-full text-sm">
                <thead class="border-y border-gray-100 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-5 py-2.5">Invoice</th>
                        <th class="px-4 py-2.5">Proyek</th>
                        <th class="px-4 py-2.5 text-right">Nominal</th>
                        <th class="px-5 py-2.5">Terbit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($d['overdue'] as $inv)
                        <tr class="cursor-pointer align-top hover:bg-blue-50/40 dark:hover:bg-blue-900/20" onclick="location.href='{{ route('proposals.show', $inv->project) }}'">
                            <td class="px-5 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-gray-100">{{ $inv->invoice_number }}</td>
                            <td class="px-4 py-3">
                                <p class="text-gray-900 dark:text-gray-100" title="{{ $inv->project->proposal_number }}">{{ $inv->project->proposal_number_short }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $clientOf($inv->project) }}</p>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap text-gray-900 dark:text-gray-100">{{ $rp($inv->amount) }}</td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <p class="text-xs text-gray-700 dark:text-gray-300">{{ $inv->display_date->translatedFormat('d M Y') }}</p>
                                <p class="text-[11px] font-medium text-rose-600 dark:text-rose-400">{{ $inv->age_days }} hari</p>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-gray-100 border-t border-gray-100 md:hidden dark:divide-gray-700 dark:border-gray-700">
            @foreach ($d['overdue'] as $inv)
                <a href="{{ route('proposals.show', $inv->project) }}" class="block space-y-1 px-4 py-3 hover:bg-blue-50/40 dark:hover:bg-blue-900/20">
                    <div class="flex items-start justify-between gap-3">
                        <p class="min-w-0 break-all font-medium text-gray-900 dark:text-gray-100">{{ $inv->invoice_number }}</p>
                        <p class="shrink-0 font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $rp($inv->amount) }}</p>
                    </div>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $inv->project->proposal_number_short }} &middot; {{ $clientOf($inv->project) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Terbit {{ $inv->display_date->translatedFormat('d M Y') }} &middot; <span class="font-medium text-rose-600 dark:text-rose-400">{{ $inv->age_days }} hari</span></p>
                </a>
            @endforeach
        </div>
    @endif
</div>
