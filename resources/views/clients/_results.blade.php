    <div class="hidden md:block bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto dark:bg-gray-800 dark:border-gray-700">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-500">
                    <th class="px-4 py-3">@include('partials.sort-link', ['key' => 'client_name', 'label' => 'Nama Klien'])</th>
                    <th class="px-4 py-3">Alamat</th>
                    <th class="px-4 py-3">@include('partials.sort-link', ['key' => 'client_type', 'label' => 'Jenis'])</th>
                    <th class="px-4 py-3 text-center">@include('partials.sort-link', ['key' => 'projects_count', 'label' => 'Dipakai di Proyek'])</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($clients as $client)
                    @php
                        // Jumlah proyek unik (lihat ClientController@index).
                        $usageCount = (int) $client->projects_count;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/60">
                        {{-- Klik nama = detail klien & proyek yang melibatkannya (2026-09-15). --}}
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('clients.show', $client) }}" class="text-gray-900 hover:text-blue-600 hover:underline dark:text-gray-100 dark:hover:text-blue-400">{{ $client->client_name }}</a>
                        </td>
                        {{-- Alamat ditampilkan (2026-09-15, feedback user): satu nama
                             seperti Bank Mandiri bisa punya banyak cabang. --}}
                        <td class="px-4 py-3 text-xs text-gray-500 whitespace-pre-line dark:text-gray-500">{{ $client->address ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-500">{{ $client->client_type }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($usageCount > 0)
                                {{-- Klik = buka List Project tersaring nama klien ini. --}}
                                <a href="{{ route('dashboard', ['q' => $client->client_name, 'mine' => 0]) }}" title="Lihat proyek klien ini"
                                   class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/40 dark:text-blue-400 dark:hover:bg-blue-900/70">{{ $usageCount }} proyek</a>
                            @else
                                <span class="text-xs text-gray-500 dark:text-gray-400">Belum dipakai</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                        @can('clients.manage')
                            <div class="flex justify-center items-center gap-1.5">
                                {{-- Ikon aksi disamakan dengan tabel lain (2026-09-15, feedback user). --}}
                                <a href="{{ route('clients.edit', $client) }}" title="Edit klien" aria-label="Edit klien"
                                   class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-100">
                                    <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                    </svg>
                                </a>

                                @if ($usageCount === 0)
                                    <form action="{{ route('clients.destroy', $client) }}" method="POST"
                                          data-confirm="Yakin hapus klien &quot;{{ $client->client_name }}&quot;? Aksi ini tidak bisa dibatalkan.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus klien" aria-label="Hapus klien"
                                                class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-red-100 hover:text-red-700 dark:text-gray-500 dark:hover:bg-red-900/30 dark:hover:text-red-300">
                                            <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <span title="Tidak bisa dihapus — masih terhubung dengan proyek"
                                          class="grid h-8 w-8 place-items-center rounded-md text-gray-300 cursor-not-allowed dark:text-gray-600">
                                        <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                        </svg>
                                    </span>
                                @endif
                            </div>
                            @elsecan('clients.view')
                            <span class="text-gray-300 text-xs dark:text-gray-500">— (lihat saja)</span>
                        @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">Belum ada klien.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Ponsel: kartu klien (2026-09-15, feedback user). --}}
    <div class="md:hidden bg-white rounded-lg border border-gray-200 shadow-sm divide-y divide-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:divide-gray-700">
        @forelse ($clients as $client)
            @php $usageCount = (int) $client->projects_count; @endphp
            <div class="space-y-1.5 px-4 py-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('clients.show', $client) }}" class="font-medium text-gray-900 hover:text-blue-600 dark:text-gray-100 dark:hover:text-blue-400">{{ $client->client_name }}</a>
                        <p class="text-xs text-gray-500 dark:text-gray-500">{{ $client->client_type }}</p>
                    </div>
                    @can('clients.manage')
                        <div class="flex shrink-0 items-center gap-1">
                            <a href="{{ route('clients.edit', $client) }}" title="Edit klien" aria-label="Edit klien"
                               class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-100">
                                <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                            </a>
                            @if ($usageCount === 0)
                                <form action="{{ route('clients.destroy', $client) }}" method="POST"
                                      data-confirm="Yakin hapus klien &quot;{{ $client->client_name }}&quot;? Aksi ini tidak bisa dibatalkan.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus klien" aria-label="Hapus klien"
                                            class="grid h-8 w-8 place-items-center rounded-md text-gray-500 hover:bg-red-100 hover:text-red-700 dark:text-gray-500 dark:hover:bg-red-900/30 dark:hover:text-red-300">
                                        <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endcan
                </div>
                <p class="text-xs text-gray-500 whitespace-pre-line line-clamp-3 dark:text-gray-500">{{ $client->address ?: '—' }}</p>
                @if ($usageCount > 0)
                    <a href="{{ route('clients.show', $client) }}" class="inline-block px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">{{ $usageCount }} proyek</a>
                @else
                    <span class="text-xs text-gray-500 dark:text-gray-400">Belum dipakai</span>
                @endif
            </div>
        @empty
            <p class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada klien.</p>
        @endforelse
    </div>

    <div>{{ $clients->links() }}</div>
