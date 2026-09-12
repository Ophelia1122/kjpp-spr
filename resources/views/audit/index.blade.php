@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto py-8 space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Log Aktivitas (Audit Log)</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $logs->total() }} aktivitas tercatat</p>
    </div>

    <form method="GET" action="{{ route('audit.index') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 flex flex-wrap gap-3 items-end dark:bg-gray-800 dark:border-gray-700">
        <div class="min-w-[180px]">
            <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Pengguna</label>
            <select name="user_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
                <option value="">Semua Pengguna</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Jenis Aksi</label>
            <input type="text" name="action" value="{{ request('action') }}" placeholder="mis. invoice, proposal"
                   class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" lang="id" class="rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Sampai Tanggal</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" lang="id" class="rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
        </div>
        <button type="submit" class="px-4 py-2 text-sm rounded-md bg-gray-800 text-white hover:bg-gray-900">Filter</button>
        @if (request()->anyFilled(['user_id', 'action', 'date_from', 'date_to']))
            <a href="{{ route('audit.index') }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700/60">Reset</a>
        @endif
    </form>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-x-auto dark:bg-gray-800 dark:border-gray-700">
        <table class="min-w-[640px] w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-400">
                    <th class="px-4 py-3">Waktu</th>
                    <th class="px-4 py-3">Pengguna</th>
                    <th class="px-4 py-3">Aksi</th>
                    <th class="px-4 py-3">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/60">
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap dark:text-gray-400">
                            {{ $log->created_at->translatedFormat('d M Y, H:i') }}
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                            {{ $log->user->name ?? 'Sistem' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 font-mono dark:bg-gray-800 dark:text-gray-400">{{ $log->action }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $log->description }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">Belum ada aktivitas tercatat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $logs->links() }}</div>
</div>
@endsection
