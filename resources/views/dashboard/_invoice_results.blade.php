    @php
        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
        $overdueDays = $overdueDays ?? \App\Http\Controllers\PaymentDashboardController::OVERDUE_DAYS;
        $currentSort = $currentSort ?? null;
        $currentDir  = $currentDir ?? 'desc';

        // Header kolom bisa diklik untuk mengurutkan — pola sama dengan List
        // Project (2026-09-14, feedback user). Klik kolom yang sama = balik arah.
        $sortUrl = function (string $key) use ($currentSort, $currentDir) {
            $dir = ($currentSort === $key && $currentDir === 'desc') ? 'asc' : 'desc';
            return request()->fullUrlWithQuery(['sort' => $key, 'dir' => $dir, 'page' => null]);
        };
        $cols = [
            ['key' => 'invoice_number', 'label' => 'No. Invoice / Kwitansi', 'class' => 'px-4 py-3'],
            ['key' => null,             'label' => 'Proyek / Klien',         'class' => 'px-4 py-3'],
            ['key' => null,             'label' => 'Keterangan',             'class' => 'px-4 py-3'],
            ['key' => 'amount',         'label' => 'Nominal',                'class' => 'px-4 py-3 text-right'],
            ['key' => 'date',           'label' => 'Terbit / Bayar',         'class' => 'px-4 py-3 whitespace-nowrap'],
            ['key' => 'status',         'label' => 'Status',                 'class' => 'px-4 py-3 whitespace-nowrap'],
            ['key' => null,             'label' => 'Aksi',                   'class' => 'px-4 py-3 text-center w-28'],
        ];
    @endphp
    <div class="bg-white rounded-b-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
        {{-- Jumlah hasil. Baris per halaman dikunci 15 (2026-09-20, feedback user). --}}
        <div class="border-b border-gray-100 px-4 py-2.5 dark:border-gray-700">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $invoices->total() }} invoice ditemukan</p>
        </div>

        <div class="hidden md:block overflow-x-auto">
        {{-- No. Kwitansi ditaruh di bawah No. Invoice & kedua tanggal digabung
             satu kolom (2026-09-13) supaya tabel muat tanpa geser samping. --}}
        <table class="min-w-[900px] w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-500">
                    @foreach ($cols as $col)
                        <th class="{{ $col['class'] }}">
                            @if ($col['key'])
                                <a href="{{ $sortUrl($col['key']) }}" class="inline-flex items-center gap-1 hover:text-gray-800 dark:hover:text-gray-200">
                                    {{ $col['label'] }}
                                    @if ($currentSort === $col['key'])
                                        <svg class="h-3 w-3 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $currentDir === 'asc' ? 'm4.5 15.75 7.5-7.5 7.5 7.5' : 'm19.5 8.25-7.5 7.5-7.5-7.5' }}"/>
                                        </svg>
                                    @else
                                        <svg class="h-3 w-3 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/>
                                        </svg>
                                    @endif
                                </a>
                            @else
                                {{ $col['label'] }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($invoices as $inv)
                    @php
                        $isPaid      = $inv->status === \App\Models\Invoice::STATUS_PAID;
                        $isCancelled = $inv->project?->isCancelled();
                        $isOverdue   = !$isPaid && !$isCancelled && $inv->age_days > $overdueDays;
                    @endphp
                    <tr data-href="{{ route('proposals.show', $inv->project) }}"
                        class="clickable-row cursor-pointer hover:bg-blue-50/40 dark:hover:bg-blue-900/20 {{ $isCancelled ? 'opacity-60' : '' }}">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ $inv->invoice_number }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $inv->kwitansi_number ? 'Kwt. ' . $inv->kwitansi_number : 'Belum ada kwitansi' }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-500">
                            <span class="font-medium text-gray-900 whitespace-nowrap dark:text-gray-100" title="{{ $inv->project->proposal_number }}">
                                {{ $inv->project->proposal_number_short }}
                            </span>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $inv->project->effective_client_name ?: '-' }}
                                @if ($isCancelled)
                                    <span class="ml-1 font-semibold text-rose-600 dark:text-rose-400">· Proyek Batal (tidak dihitung)</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-500">{{ $inv->term_description ?? $inv->invoice_type }}</td>
                        <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap text-gray-900 dark:text-gray-100">{{ $rp($inv->amount) }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap dark:text-gray-500">
                            <div>{{ $inv->display_date->translatedFormat('d M Y') }}</div>
                            <div class="{{ $inv->payment_date ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $inv->payment_date?->translatedFormat('d M Y') ?? 'belum dibayar' }}
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{-- "Dibayar", bukan "Lunas" (2026-09-13): invoice DP yang dibayar
                                 belum berarti proyeknya lunas. --}}
                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold {{ $isPaid ? 'bg-emerald-100 text-emerald-700 border border-emerald-300 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800' : 'bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800' }}">
                                {{ $isPaid ? 'Dibayar' : 'Belum Dibayar' }}
                            </span>
                            @unless ($isPaid)
                                <div class="mt-1 text-[11px] {{ $isOverdue ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-400' }}"
                                     title="Dihitung sejak tanggal terbit; tertunggak bila lebih dari {{ $overdueDays }} hari">
                                    {{ $isOverdue ? '⚠ ' : '' }}{{ $inv->age_days }} hari
                                </div>
                            @endunless
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-center items-center gap-1.5" data-row-actions>
                                @can('invoices.manage')
                                    @if (!$isPaid && !$isCancelled)
                                        <button type="button" title="Tandai Dibayar" aria-label="Tandai Dibayar"
                                                onclick="openMarkPaidModal('{{ route('invoices.markAsPaid', $inv) }}', {{ \Illuminate\Support\Js::from($inv->invoice_number . ' — ' . $rp($inv->amount)) }})"
                                                class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-emerald-100 hover:text-emerald-700 dark:text-gray-500 dark:hover:bg-emerald-900/30 dark:hover:text-emerald-300">
                                            <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                            </svg>
                                        </button>
                                    @endif
                                @endcan
                                {{-- Unduh ▾ menggantikan 4 ikon unduh (2026-09-14, feedback user). --}}
                                @can('invoices.view')
                                    @include('partials.invoice-download-menu', [
                                        'inv' => $inv,
                                        'kwitansiAvailable' => $isPaid || $inv->project->isPaymentDeferred(),
                                    ])
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">Belum ada invoice yang cocok dengan filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        {{-- Ponsel: kartu invoice (2026-09-15, feedback user). --}}
        <div class="divide-y divide-gray-100 md:hidden dark:divide-gray-700">
            @forelse ($invoices as $inv)
                @php
                    $isPaid      = $inv->status === \App\Models\Invoice::STATUS_PAID;
                    $isCancelled = $inv->project?->isCancelled();
                    $isOverdue   = !$isPaid && !$isCancelled && $inv->age_days > $overdueDays;
                @endphp
                <div data-href="{{ route('proposals.show', $inv->project) }}"
                     onclick="if (!event.target.closest('[data-row-actions]')) location.href = this.dataset.href"
                     class="cursor-pointer space-y-2 px-4 py-3 hover:bg-blue-50/40 dark:hover:bg-blue-900/20 {{ $isCancelled ? 'opacity-60' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 break-all dark:text-gray-100">{{ $inv->invoice_number }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-500">
                                {{ $inv->project->proposal_number_short }} &middot; {{ $inv->project->effective_client_name ?: '-' }}
                                @if ($isCancelled)
                                    <span class="font-semibold text-rose-600 dark:text-rose-400">· Batal</span>
                                @endif
                            </p>
                        </div>
                        <p class="shrink-0 font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $rp($inv->amount) }}</p>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-500">{{ $inv->term_description ?? $inv->invoice_type }}</p>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                            <span class="inline-block px-2 py-0.5 rounded-full font-semibold {{ $isPaid ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' }}">
                                {{ $isPaid ? 'Dibayar' : 'Belum Dibayar' }}
                            </span>
                            <span class="text-gray-500 dark:text-gray-500">Terbit {{ $inv->display_date->translatedFormat('d M Y') }}</span>
                            @if ($isPaid)
                                <span class="text-emerald-600 dark:text-emerald-400">Bayar {{ $inv->payment_date?->translatedFormat('d M Y') }}</span>
                            @else
                                <span class="{{ $isOverdue ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $isOverdue ? '⚠ ' : '' }}{{ $inv->age_days }} hari</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5" data-row-actions>
                            @can('invoices.manage')
                                @if (!$isPaid && !$isCancelled)
                                    <button type="button" title="Tandai Dibayar" aria-label="Tandai Dibayar"
                                            onclick="openMarkPaidModal('{{ route('invoices.markAsPaid', $inv) }}', {{ \Illuminate\Support\Js::from($inv->invoice_number . ' — ' . $rp($inv->amount)) }})"
                                            class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-emerald-100 hover:text-emerald-700 dark:text-gray-500 dark:hover:bg-emerald-900/30 dark:hover:text-emerald-300">
                                        <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    </button>
                                @endif
                            @endcan
                            @can('invoices.view')
                                @include('partials.invoice-download-menu', [
                                    'inv' => $inv,
                                    'kwitansiAvailable' => $isPaid || $inv->project->isPaymentDeferred(),
                                ])
                            @endcan
                        </div>
                    </div>
                </div>
            @empty
                <p class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada invoice yang cocok dengan filter ini.</p>
            @endforelse
        </div>
    </div>

    <div>{{ $invoices->links() }}</div>
