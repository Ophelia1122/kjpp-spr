@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto py-8 space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Daftar Klien</h1>
            <p class="text-sm text-gray-500">{{ $clients->total() }} klien terdaftar</p>
        </div>
    </div>

    <form method="GET" action="{{ route('clients.index') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 flex gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama klien..."
               class="flex-1 rounded-md border-gray-300 shadow-sm text-sm">
        <button type="submit" class="px-4 py-2 text-sm rounded-md bg-gray-800 text-white hover:bg-gray-900">Cari</button>
        @if (request('q'))
            <a href="{{ route('clients.index') }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50">Reset</a>
        @endif
    </form>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    <th class="px-4 py-3">Nama Klien</th>
                    <th class="px-4 py-3">Jenis</th>
                    <th class="px-4 py-3">Kontak</th>
                    <th class="px-4 py-3 text-center">Dipakai di Proyek</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($clients as $client)
                    @php
                        $usageCount = $client->projects_as_instructing_client_count + $client->projects_as_intended_user_count;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $client->client_name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $client->client_type }}</td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ $client->contact_person ?? '-' }}
                            @if ($client->phone)<br><span class="text-xs">{{ $client->phone }}</span>@endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($usageCount > 0)
                                <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">{{ $usageCount }} proyek</span>
                            @else
                                <span class="text-xs text-gray-400">Belum dipakai</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                        @can('clients.manage')
                            <div class="flex justify-center gap-3">
                                <a href="{{ route('clients.edit', $client) }}" class="text-blue-600 hover:text-blue-800 font-medium">Edit</a>

                                @if ($usageCount === 0)
                                    <form action="{{ route('clients.destroy', $client) }}" method="POST"
                                          onsubmit="return confirm('Yakin hapus klien &quot;{{ $client->client_name }}&quot;? Aksi ini tidak bisa dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Hapus</button>
                                    </form>
                                @else
                                    <span class="text-gray-300 cursor-not-allowed" title="Tidak bisa dihapus — masih terhubung dengan proyek">Hapus</span>
                                @endif
                            </div>
                            @elsecan('clients.view')
                            <span class="text-gray-300 text-xs">— (lihat saja)</span>
                        @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-400">Belum ada klien.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $clients->links() }}</div>
</div>
@endsection