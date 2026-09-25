@extends('layouts.app')

@section('title', 'Log Aktivitas')

@section('content')
<div class="max-w-7xl mx-auto py-8 space-y-6">

    {{-- Tombol unduh & kosongkan log (2026-09-24, permintaan user).
         Mengosongkan log hanya untuk Administrator dan meminta kata sandi. --}}
    <x-page-header title="Log Aktivitas" subtitle="{{ $logs->total() }} aktivitas tercatat">
        <a href="{{ route('audit.export', request()->query()) }}"
           title="Unduh Excel sesuai filter" aria-label="Unduh Excel sesuai filter"
           class="inline-flex h-[38px] w-[38px] items-center justify-center rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
            <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
        </a>
        @if (auth()->user()->isAdministrator())
            <button type="button" onclick="openClearLogModal()"
                    class="inline-flex h-[38px] items-center gap-1.5 rounded-md border border-rose-300 px-4 text-sm font-medium text-rose-600 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-400 dark:hover:bg-rose-900/30">
                <svg aria-hidden="true" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                </svg>
                Kosongkan Log
            </button>
        @endif
    </x-page-header>

    @if (auth()->user()->isAdministrator())
        <div id="clearLogModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-gray-900/60 p-4" role="dialog" aria-modal="true"
             onclick="if (event.target === this) closeClearLogModal()">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-800">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Kosongkan Log Aktivitas</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    Seluruh <b>{{ number_format($logs->total(), 0, ',', '.') }}</b> baris log akan dihapus permanen dan
                    <b>tidak bisa dikembalikan</b>. Unduh Export Excel lebih dulu bila masih diperlukan.
                </p>
                <form method="POST" action="{{ route('audit.clear') }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label for="clear_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kata sandi Anda</label>
                        <input type="password" name="password" id="clear_password" required autocomplete="current-password"
                               class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900">
                        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" onclick="closeClearLogModal()"
                                class="inline-flex h-[38px] items-center rounded-md border border-gray-300 px-4 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">Batal</button>
                        <button type="submit"
                                class="inline-flex h-[38px] items-center rounded-md bg-rose-600 px-4 text-sm font-medium text-white hover:bg-rose-700">Hapus Semua Log</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            const clearLogModal = document.getElementById('clearLogModal');
            document.body.appendChild(clearLogModal);

            function openClearLogModal() {
                clearLogModal.classList.remove('hidden');
                clearLogModal.classList.add('flex');
                document.getElementById('clear_password').focus();
            }
            function closeClearLogModal() {
                clearLogModal.classList.add('hidden');
                clearLogModal.classList.remove('flex');
            }
            @if ($errors->has('password'))
                openClearLogModal();
            @endif
        </script>
    @endif

    {{-- Filter langsung diterapkan saat diubah, tanpa tombol (seragam, 2026-09-15). --}}
    <form id="auditFilter" method="GET" action="{{ route('audit.index') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 flex flex-wrap gap-3 items-end dark:bg-gray-800 dark:border-gray-700">
        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
        <div class="min-w-[180px]">
            <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-500">Pengguna</label>
            <select name="user_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
                <option value="">Semua Pengguna</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-500">Jenis Aksi</label>
            <input type="text" name="action" value="{{ request('action') }}" placeholder="mis. invoice, proposal"
                   class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-500">Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" lang="id" class="rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-500">Sampai Tanggal</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" lang="id" class="rounded-md border-gray-300 shadow-sm text-sm dark:border-gray-600">
        </div>
        {{-- Rentang aktif ditulis ulang dalam bahasa Indonesia (2026-09-20,
             hasil audit UI) — urutan hari/bulan pada kolom tanggal mengikuti
             pengaturan browser, jadi mudah tertukar. --}}
        @if (request('date_from') || request('date_to'))
            <p class="basis-full text-xs text-gray-500 dark:text-gray-400">
                Menampilkan
                {{ request('date_from') ? \Illuminate\Support\Carbon::parse(request('date_from'))->translatedFormat('d F Y') : 'awal data' }}
                &ndash;
                {{ request('date_to') ? \Illuminate\Support\Carbon::parse(request('date_to'))->translatedFormat('d F Y') : 'hari ini' }}
            </p>
        @endif
        @if (request()->anyFilled(['user_id', 'action', 'date_from', 'date_to']))
            <a href="{{ route('audit.index') }}" class="inline-flex h-[38px] items-center justify-center gap-1.5 rounded-md border border-gray-300 px-4 text-sm font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">Reset</a>
        @endif
    </form>

    {{-- Tabel untuk layar lebar; layar sempit memakai kartu (2026-09-20). --}}
    <div class="hidden overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm md:block dark:border-gray-700 dark:bg-gray-800">
        <table class="min-w-[640px] w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide dark:text-gray-500">
                    <th class="px-4 py-3">Waktu</th>
                    <th class="px-4 py-3">Pengguna</th>
                    <th class="px-4 py-3">Aksi</th>
                    <th class="px-4 py-3">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/60">
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap dark:text-gray-500">
                            {{-- Zona waktu aplikasi = Asia/Jakarta (2026-09-14, feedback user). --}}
                            {{ $log->created_at->translatedFormat('d M Y, H:i') }} WIB
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                            {{ $log->user->name ?? 'Sistem' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 font-mono dark:bg-gray-800 dark:text-gray-500">{{ $log->action }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $log->description }}
                            {{-- Catatan/alasan langkah alur proyek (2026-09-14). --}}
                            @if ($log->note)
                                <span class="mt-0.5 block text-xs italic text-gray-500 dark:text-gray-500">Catatan: {{ $log->note }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Tidak ada aktivitas yang cocok</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Longgarkan filter pengguna, jenis aksi, atau rentang tanggal.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ---------- KARTU (HP) ---------- --}}
    <div class="space-y-2 md:hidden">
        @forelse ($logs as $log)
            <div class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $log->user->name ?? 'Sistem' }}</p>
                    <span class="shrink-0 text-[11px] text-gray-500 dark:text-gray-400">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</span>
                </div>
                <span class="mt-1 inline-block rounded-full bg-gray-100 px-2 py-0.5 font-mono text-[11px] text-gray-600 dark:bg-gray-900 dark:text-gray-300">{{ $log->action }}</span>
                <p class="mt-1 text-xs text-gray-700 dark:text-gray-300">{{ $log->description }}</p>
                @if ($log->note)
                    <p class="mt-0.5 text-xs italic text-gray-500 dark:text-gray-400">Catatan: {{ $log->note }}</p>
                @endif
            </div>
        @empty
            <p class="rounded-lg border border-gray-200 bg-white p-8 text-center text-sm text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Belum ada aktivitas tercatat.</p>
        @endforelse
    </div>

    <div>{{ $logs->links() }}</div>
</div>

<script>
    (function () {
        const form = document.getElementById('auditFilter');
        let timer = null;
        form.querySelectorAll('select, input[type="date"]').forEach((el) => el.addEventListener('change', () => form.submit()));
        form.querySelectorAll('input[type="text"]').forEach((el) => {
            el.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => form.submit(), 1000); });
            // Setelah halaman dimuat ulang saat mengetik, kursor kembali ke kolom.
            if (el.value) { el.focus(); el.setSelectionRange(el.value.length, el.value.length); }
        });
    })();
</script>
@endsection
