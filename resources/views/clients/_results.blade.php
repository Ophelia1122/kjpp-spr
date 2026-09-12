    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $clients->total() }} klien terdaftar</p>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto dark:bg-gray-800 dark:border-gray-700">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">
                    <th class="px-4 py-3">Nama Klien</th>
                    <th class="px-4 py-3">Jenis</th>
                    <th class="px-4 py-3">Kontak</th>
                    <th class="px-4 py-3 text-center">Dipakai di Proyek</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($clients as $client)
                    @php
                        $usageCount = $client->projects_as_instructing_client_count + $client->projects_as_intended_user_count;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/60">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $client->client_name }}</td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $client->client_type }}</td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                            {{ $client->contact_person ?? '-' }}
                            @if ($client->phone)<br><span class="text-xs">{{ $client->phone }}</span>@endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($usageCount > 0)
                                <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">{{ $usageCount }} proyek</span>
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-500">Belum dipakai</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                        @can('clients.manage')
                            <div class="flex justify-center gap-3">
                                <a href="{{ route('clients.edit', $client) }}" class="text-blue-600 hover:text-blue-800 font-medium dark:text-blue-400 dark:hover:text-blue-300">Edit</a>

                                @if ($usageCount === 0)
                                    <form action="{{ route('clients.destroy', $client) }}" method="POST"
                                          onsubmit="return confirm('Yakin hapus klien &quot;{{ $client->client_name }}&quot;? Aksi ini tidak bisa dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium dark:text-red-400 dark:hover:text-red-300">Hapus</button>
                                    </form>
                                @else
                                    <span class="text-gray-300 cursor-not-allowed dark:text-gray-400" title="Tidak bisa dihapus — masih terhubung dengan proyek">Hapus</span>
                                @endif
                            </div>
                            @elsecan('clients.view')
                            <span class="text-gray-300 text-xs dark:text-gray-400">— (lihat saja)</span>
                        @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">Belum ada klien.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $clients->links() }}</div>
