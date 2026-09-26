{{-- Perlu Ditindaklanjuti: proposal Draft / DP Invoicing + umur sejak dibuat.
     Dipindah dari Ringkasan Project ke Beranda General Admin (2026-09-21,
     feedback user). Butuh $followUps. --}}
@if ($followUps->isNotEmpty())
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm lift dark:border-gray-700 dark:bg-gray-800">
        <div class="px-6 pt-6">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">Perlu Ditindaklanjuti</h2>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                Proposal berstatus Draft Proposal / DP Invoicing &mdash; reminder sudah berapa hari sejak dibuat.
            </p>
        </div>
        <div class="hidden overflow-x-auto md:block">
        <table class="min-w-[640px] w-full text-sm mt-3">
            <thead class="bg-gray-50 border-y border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">
                    <th class="px-6 py-3">No. Proposal</th>
                    <th class="px-6 py-3">Nama Klien</th>
                    <th class="px-6 py-3 w-52">Status</th>
                    <th class="px-6 py-3 text-right whitespace-nowrap">Reminder</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($followUps as $p)
                    @php
                        $daysSinceCreated = (int) $p->created_at->diffInDays(now());
                        $reminderTone = match (true) {
                            $daysSinceCreated >= 7 => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
                            $daysSinceCreated >= 3 => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                            default                => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                        };
                    @endphp
                    <tr class="hover:bg-blue-50/40 dark:hover:bg-blue-900/20">
                        <td class="px-6 py-3 font-medium text-gray-900 dark:text-gray-100">
                            <a href="{{ route('proposals.show', $p) }}" class="whitespace-nowrap hover:text-blue-700 dark:hover:text-blue-300" title="{{ $p->proposal_number }}" aria-label="{{ $p->proposal_number }}">{{ $p->proposal_number_short }}</a>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $p->effective_client_name ?: '-' }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap {{ $p->status_badge_classes }}" title="{{ $p->status }}">
                                {{ $p->status_short }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right whitespace-nowrap">
                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold {{ $reminderTone }}">
                                {{ $daysSinceCreated }} hari
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        <div class="divide-y divide-gray-100 md:hidden dark:divide-gray-700">
            @foreach ($followUps as $p)
                @php $hari = (int) $p->created_at->diffInDays(now()); @endphp
                <a href="{{ route('proposals.show', $p) }}" class="flex items-start justify-between gap-3 px-4 py-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $p->proposal_number_short }}</p>
                        <p class="truncate text-xs text-gray-600 dark:text-gray-400">{{ $p->effective_client_name ?: '-' }}</p>
                        <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $p->status_badge_classes }}">{{ $p->status_short }}</span>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $hari >= 7 ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' : ($hari >= 3 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300') }}">
                        {{ $hari }} hari
                    </span>
                </a>
            @endforeach
        </div>
    {{-- Paginasi panel: maksimal 5 baris per halaman (2026-09-24). --}}
    @if ($followUps->hasPages())
        <div class="border-t border-gray-100 px-5 py-3 dark:border-gray-700">{{ $followUps->links() }}</div>
    @endif
    </div>
@endif
