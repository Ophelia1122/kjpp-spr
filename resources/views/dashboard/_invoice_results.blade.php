    @php
        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    @endphp
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto lift dark:bg-gray-800 dark:border-gray-700">
        <table class="min-w-[900px] w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">
                    <th class="px-4 py-3">No. Invoice</th>
                    <th class="px-4 py-3">No. Kwitansi</th>
                    <th class="px-4 py-3">Proyek / Klien</th>
                    <th class="px-4 py-3">Keterangan</th>
                    <th class="px-4 py-3 text-right">Nominal</th>
                    <th class="px-4 py-3 whitespace-nowrap">Status</th>
                    <th class="px-4 py-3 text-center w-24">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($invoices as $inv)
                    <tr class="hover:bg-blue-50/40 dark:hover:bg-blue-900/20">
                        <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-gray-100">{{ $inv->invoice_number }}</td>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap dark:text-gray-400">{{ $inv->kwitansi_number ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            <a href="{{ route('proposals.show', $inv->project) }}" class="font-medium text-gray-900 hover:text-blue-700 dark:text-gray-100 dark:hover:text-blue-300">
                                {{ $inv->project->proposal_number }}
                            </a>
                            <div class="text-xs text-gray-400 dark:text-gray-500">{{ $inv->project->instructingClient->client_name ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $inv->term_description ?? $inv->invoice_type }}</td>
                        <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap">{{ $rp($inv->amount) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold {{ $inv->status === 'Paid' ? 'bg-emerald-100 text-emerald-700 border border-emerald-300 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800' : 'bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800' }}">
                                {{ $inv->status === 'Paid' ? 'Lunas' : 'Belum Dibayar' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-center items-center gap-1.5">
                                @can('invoices.view')
                                    <a href="{{ route('invoices.exportInvoice', $inv) }}" title="Cetak Invoice"
                                       class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100">
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                        </svg>
                                    </a>
                                    @if ($inv->status === 'Paid')
                                        <a href="{{ route('invoices.exportKwitansi', $inv) }}" title="Cetak Kwitansi"
                                           class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-teal-100 hover:text-teal-700 dark:text-gray-400 dark:hover:bg-teal-900/30 dark:hover:text-teal-300">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                            </svg>
                                        </a>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">Belum ada invoice yang cocok dengan filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $invoices->links() }}</div>
