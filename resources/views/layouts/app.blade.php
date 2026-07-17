<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Internal Web-App KJPP')</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">

        {{-- ===================== SIDEBAR / TASKBAR ===================== --}}
        <aside class="w-64 bg-gray-900 text-gray-100 flex-shrink-0 hidden lg:flex lg:flex-col">
            <div class="px-6 py-5 border-b border-gray-800">
                <div class="text-lg font-bold text-white">KJPP SPR</div>
                <div class="text-xs text-gray-400">Internal Web-App</div>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                          {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <span>📊</span> Dashboard Proyek
                </a>
                <a href="{{ route('proposals.create') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                          {{ request()->routeIs('proposals.create') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <span>📝</span> Buat Proposal Baru
                </a>

                <div class="pt-4 mt-4 border-t border-gray-800">
                    <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Alur Kerja</p>
                    <div class="px-3 text-xs text-gray-500 leading-relaxed space-y-1">
                        <p>1. Buat Proposal &rarr; Cetak PDF</p>
                        <p>2. Klien Setuju &rarr; Invoice DP</p>
                        <p>3. DP Paid &rarr; Input Penilai</p>
                        <p>4. Draf Selesai &rarr; Invoice Pelunasan</p>
                        <p>5. Pelunasan Paid &rarr; No. Laporan</p>
                    </div>
                </div>
            </nav>

            <div class="px-6 py-4 border-t border-gray-800 text-xs text-gray-500">
                &copy; {{ date('Y') }} KJPP Sugianto Prasodjo dan Rekan
            </div>
        </aside>

        {{-- ===================== MAIN CONTENT ===================== --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- Topbar mobile (sidebar disembunyikan di layar kecil) --}}
            <header class="lg:hidden bg-gray-900 text-white px-4 py-3 flex items-center justify-between">
                <div class="font-bold">KJPP SPR</div>
                <nav class="flex gap-4 text-sm">
                    <a href="{{ route('dashboard') }}" class="text-gray-300 hover:text-white">Dashboard</a>
                    <a href="{{ route('proposals.create') }}" class="text-gray-300 hover:text-white">+ Proposal</a>
                </nav>
            </header>

            <main class="flex-1 px-4 lg:px-8 py-6">
                {{-- ===================== FLASH MESSAGE GLOBAL ===================== --}}
                {{-- Ditaruh di layout supaya SEMUA halaman otomatis dapat notifikasi
                     ini tanpa perlu menulis ulang blok session() di tiap view. --}}
                @if (session('success'))
                    <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3 flex items-start justify-between gap-3">
                        <span>✅ {{ session('success') }}</span>
                        <button type="button" onclick="this.closest('div').remove()" class="text-green-500 hover:text-green-700 leading-none">&times;</button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3 flex items-start justify-between gap-3">
                        <span>⚠️ {{ session('error') }}</span>
                        <button type="button" onclick="this.closest('div').remove()" class="text-red-500 hover:text-red-700 leading-none">&times;</button>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="mb-4 rounded-md bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm px-4 py-3 flex items-start justify-between gap-3">
                        <span>⚠️ {{ session('warning') }}</span>
                        <button type="button" onclick="this.closest('div').remove()" class="text-yellow-500 hover:text-yellow-700 leading-none">&times;</button>
                    </div>
                @endif

                @if (session('info'))
                    <div class="mb-4 rounded-md bg-blue-50 border border-blue-200 text-blue-800 text-sm px-4 py-3 flex items-start justify-between gap-3">
                        <span>ℹ️ {{ session('info') }}</span>
                        <button type="button" onclick="this.closest('div').remove()" class="text-blue-500 hover:text-blue-700 leading-none">&times;</button>
                    </div>
                @endif

                {{-- Validasi form (422) — tampil otomatis kalau ada $errors dari redirect back() --}}
                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3">
                        <p class="font-semibold mb-1">⚠️ Terjadi kesalahan input:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
