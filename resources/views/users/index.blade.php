@extends('layouts.app')
 
@section('content')
<div class="max-w-5xl mx-auto py-8 space-y-6">
 
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Kelola Pengguna</h1>
            <p class="text-sm text-gray-500">{{ $users->total() }} pengguna terdaftar</p>
        </div>
        <a href="{{ route('users.create') }}"
           class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
            + Tambah Pengguna
        </a>
    </div>
 
    <form method="GET" action="{{ route('users.index') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 flex gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email..."
               class="flex-1 rounded-md border-gray-300 shadow-sm text-sm">
        <button type="submit" class="px-4 py-2 text-sm rounded-md bg-gray-800 text-white hover:bg-gray-900">Cari</button>
    </form>
 
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-[640px] w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Jabatan</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">
                            {{ $user->name }}
                            @if ($user->id === auth()->id())
                                <span class="text-xs text-gray-400">(Anda)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $user->role->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $user->jabatan ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($user->is_active)
                                <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">Aktif</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-200 text-gray-500">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-3">
                                <a href="{{ route('users.edit', $user) }}" class="text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                                @if ($user->id !== auth()->id())
                                    <form action="{{ route('users.destroy', $user) }}" method="POST"
                                          onsubmit="return confirm('Yakin hapus pengguna {{ $user->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Belum ada pengguna.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
 
    <div>{{ $users->links() }}</div>
</div>
@endsection