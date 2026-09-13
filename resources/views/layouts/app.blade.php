<!DOCTYPE html>
<html lang="id" class="{{ auth()->check() && auth()->user()->dark_mode ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Internal Web-App KJPP')</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @custom-variant dark (&:where(.dark, .dark *));
    </style>
    <style>
        /* ---- Transisi halus lintas fitur ---- */
        :root { --ease: cubic-bezier(.16,.84,.44,1); }

        /* Isi halaman muncul dengan fade + naik tipis tiap navigasi. */
        @keyframes appear { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        main > * { animation: appear .32s var(--ease) both; }

        /* Elemen interaktif: transisi warna/bayangan/skala yang konsisten. */
        a, button, summary, [role="button"],
        input, select, textarea,
        .card, [class*="rounded-lg"], [class*="rounded-md"] {
            transition: background-color .16s ease, border-color .16s ease,
                        color .16s ease, box-shadow .18s ease,
                        transform .12s var(--ease), opacity .16s ease;
        }
        button:not(:disabled):active, a:active, [role="button"]:active { transform: translateY(1px) scale(.985); }

        /* Baris tabel & item sidebar. */
        tbody tr { transition: background-color .12s ease; }
        #sidebar a, #sidebar button { transition: background-color .16s ease, color .16s ease, transform .12s var(--ease); }

        /* Kartu putih terangkat tipis saat hover. */
        .lift { transition: box-shadow .2s ease, transform .2s var(--ease); }
        .lift:hover { box-shadow: 0 10px 25px -12px rgba(0,0,0,.18); transform: translateY(-2px); }

        /* Hargai preferensi kurangi animasi. */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .001ms !important; transition-duration: .001ms !important; }
        }

        /* Default mode gelap utk SEMUA input/select/textarea teks — Tailwind
           preflight reset bg jadi transparent + warna teks browser bawaan
           (hitam), jadi tanpa ini kolom form jadi tak terbaca di atas kartu
           gelap. Tak ada elemen form yg punya kelas dark warna sendiri
           di codebase (dicek via grep) jadi aman pakai spesifisitas normal. */
        .dark input:not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="file"]):not([type="submit"]):not([type="button"]):not([type="color"]),
        .dark select,
        .dark textarea {
            background-color: #111827;
            color: #f3f4f6;
        }
        .dark input::placeholder, .dark textarea::placeholder {
            color: #6b7280;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-950">
    <div class="flex min-h-screen">

        {{-- ===================== OVERLAY (mobile, klik untuk tutup sidebar) ===================== --}}
        <div id="sidebarOverlay" onclick="closeSidebar()"
             class="hidden fixed inset-0 bg-black/50 z-30 lg:hidden"></div>

        {{-- ===================== SIDEBAR ===================== --}}
        {{-- Desktop: selalu tampil (lg:translate-x-0). Mobile: default
             tersembunyi di luar layar (-translate-x-full), digeser masuk
             lewat JS toggleSidebar() saat tombol hamburger diklik. --}}
        <aside id="sidebar"
               class="fixed inset-y-0 left-0 z-40 w-64 bg-gray-900 text-gray-100 flex flex-col
                      transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out">
            <div class="px-6 py-5 border-b border-gray-800 flex items-center justify-between">
                <div>
                    <div class="text-lg font-bold text-white">KJPP SPR</div>
                    <div class="text-xs text-gray-400">Workshop Kebagusan</div>
                </div>
                <button onclick="closeSidebar()" class="lg:hidden text-gray-400 hover:text-white text-xl leading-none">&times;</button>
            </div>

            {{-- Scrollbar disembunyikan (2026-09-13, feedback user): menu tetap
                 bisa digulir di layar pendek, tapi tanpa batang scroll yang mengganggu. --}}
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @auth
                    {{-- Sidebar dibagi 3 bagian (2026-09-15, feedback user):
                         1) pekerjaan proyek, 2) ringkasan/pembayaran/klien,
                         3) Pengaturan Sistem. "Buat Proposal Baru" sengaja TIDAK
                         di sidebar — aksinya lewat tombol di List Project. --}}

                    {{-- ===== BAGIAN 1: Beranda, List Project, Timeline Project ===== --}}
                    @can('dashboard.view')
                        <a href="{{ route('home') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                {{ request()->routeIs('home') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg> Beranda
                        </a>
                        <a href="{{ route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/></svg> List Project
                        </a>
                        <a href="{{ route('timeline') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                {{ request()->routeIs('timeline') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg> Timeline Project
                        </a>
                    @endcan

                    {{-- ===== BAGIAN 2: Ringkasan Project, Dashboard Pembayaran, Database Klien ===== --}}
                    @canany(['dashboard.overview', 'invoices.view', 'clients.view'])
                        <div class="pt-4 mt-4 border-t border-gray-800 space-y-1">
                            {{-- Ringkasan manajemen — Surveyor tidak punya izin ini. --}}
                            @can('dashboard.overview')
                                <a href="{{ route('dashboard.overview') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                        {{ request()->routeIs('dashboard.overview') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg> Ringkasan Project
                                </a>
                            @endcan

                            {{-- Rekap Invoice/DP & Kwitansi lintas proyek — izin sama dg Invoice (Surveyor tidak punya). --}}
                            @can('invoices.view')
                                <a href="{{ route('dashboard.pembayaran') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                        {{ request()->routeIs('dashboard.pembayaran') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg> Dashboard Pembayaran
                                </a>
                            @endcan

                            @can('clients.view')
                                <a href="{{ route('clients.index') }}"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                          {{ request()->routeIs('clients.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/></svg> Database Klien
                                </a>
                            @endcan
                        </div>
                    @endcanany

                    {{-- ===== BAGIAN 3: Pengaturan Sistem ===== --}}

                    @canany(['roles.manage', 'users.manage', 'banks.manage', 'audit.view'])
                        <div class="pt-4 mt-4 border-t border-gray-800">
                            <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Pengaturan Sistem</p>

                            @can('users.manage')
                                <a href="{{ route('users.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                        {{ request()->routeIs('users.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg> Kelola Pengguna
                                </a>
                            @endcan

                            @can('roles.manage')
                                <a href="{{ route('roles.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                        {{ request()->routeIs('roles.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg> Kelola Role & Izin
                                </a>
                            @endcan

                            @can('banks.manage')
                                <a href="{{ route('banks.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                        {{ request()->routeIs('banks.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z"/></svg> Kelola Rekening Bank
                                </a>
                            @endcan

                            @can('audit.view')
                                <a href="{{ route('audit.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                        {{ request()->routeIs('audit.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg> Log Aktivitas
                                </a>
                            @endcan
                        </div>
                    @endcanany
                @endauth
            </nav>

            {{-- ===================== INFO USER + LOGOUT ===================== --}}
             @auth
                <div class="px-4 py-4 border-t border-gray-800">
                    <a href="{{ route('profile.show') }}"
                       class="group -mx-2 flex items-center gap-3 rounded-md px-2 py-2 hover:bg-gray-800 {{ request()->routeIs('profile.*') ? 'bg-gray-800' : '' }}">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-gray-700 text-sm font-semibold text-white uppercase group-hover:bg-blue-600">
                            {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium text-white">{{ auth()->user()->name }}</span>
                            <span class="block truncate text-xs text-gray-400">{{ auth()->user()->role->name ?? '-' }} · Profil Saya</span>
                        </span>
                    </a>

                    {{-- 2 kolom: Logout (kiri) + toggle mode gelap/terang (kanan). --}}
                    <div class="mt-2 grid grid-cols-2 items-center gap-2">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-left text-sm text-red-400 hover:text-red-300">
                                <span class="inline-flex items-center gap-1.5"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>Logout</span>
                            </button>
                        </form>

                        <label class="inline-flex items-center justify-self-end gap-2 cursor-pointer" title="Mode Gelap/Terang">
                            <span id="themeToggleIcon" class="text-xs">{{ auth()->user()->dark_mode ? '☀️' : '🌙' }}</span>
                            <span class="relative inline-block h-5 w-9 shrink-0">
                                <input type="checkbox" id="themeToggleCheckbox" class="peer sr-only" onchange="toggleTheme()"
                                       {{ auth()->user()->dark_mode ? 'checked' : '' }}>
                                <span class="absolute inset-0 rounded-full bg-gray-600 transition-colors peer-checked:bg-blue-600"></span>
                                <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-4"></span>
                            </span>
                        </label>
                    </div>
                </div>
            @endauth
        </aside>

        {{-- ===================== MAIN CONTENT ===================== --}}
        {{-- Sidebar desktop = position:fixed (selalu terlihat, blok bawah tak
             ikut ke dasar halaman). Konten digeser ke kanan lewat lg:pl-64. --}}
        <div class="flex-1 flex flex-col min-w-0 lg:pl-64">

            {{-- Topbar — SEKARANG cuma tombol hamburger + judul, bukan
                 daftar link cramped seperti sebelumnya. Semua navigasi
                 tetap dari sidebar yang sama (di-toggle di mobile). --}}
            <header class="lg:hidden bg-gray-900 text-white px-4 py-3 flex items-center gap-3 sticky top-0 z-20">
                <button onclick="openSidebar()" class="text-2xl leading-none" aria-label="Buka menu">☰</button>
                <div class="font-bold">KJPP SPR</div>
            </header>

            <main class="flex-1 px-4 lg:px-8 py-6">
                @if (session('success'))
                    <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 text-sm px-4 py-3 flex items-start justify-between gap-3">
                        <span>✅ {{ session('success') }}</span>
                        <button type="button" onclick="this.closest('div').remove()" class="text-green-500 hover:text-green-700 dark:hover:text-green-300 leading-none">&times;</button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 text-sm px-4 py-3 flex items-start justify-between gap-3">
                        <span>⚠️ {{ session('error') }}</span>
                        <button type="button" onclick="this.closest('div').remove()" class="text-red-500 hover:text-red-700 dark:hover:text-red-300 leading-none">&times;</button>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="mb-4 rounded-md bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-300 text-sm px-4 py-3 flex items-start justify-between gap-3">
                        <span>⚠️ {{ session('warning') }}</span>
                        <button type="button" onclick="this.closest('div').remove()" class="text-yellow-500 hover:text-yellow-700 dark:hover:text-yellow-300 leading-none">&times;</button>
                    </div>
                @endif

                @if (session('info'))
                    <div class="mb-4 rounded-md bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-300 text-sm px-4 py-3 flex items-start justify-between gap-3">
                        <span>ℹ️ {{ session('info') }}</span>
                        <button type="button" onclick="this.closest('div').remove()" class="text-blue-500 hover:text-blue-700 dark:hover:text-blue-300 leading-none">&times;</button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 text-sm px-4 py-3">
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

    <script>
        function openSidebar() {
            document.getElementById('sidebar').classList.remove('-translate-x-full');
            document.getElementById('sidebarOverlay').classList.remove('hidden');
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.add('-translate-x-full');
            document.getElementById('sidebarOverlay').classList.add('hidden');
        }

        function toggleTheme() {
            const isDark = document.getElementById('themeToggleCheckbox').checked;
            document.documentElement.classList.toggle('dark', isDark);
            document.getElementById('themeToggleIcon').textContent = isDark ? '☀️' : '🌙';
            fetch('{{ route('profile.toggleTheme') }}', {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            }).catch(() => {});
        }

        // ===================== LIVE SEARCH (debounce 1 detik, tanpa reload) =====================
        // Dipakai bareng di semua halaman berkotak cari (Dashboard Project,
        // Database Klien, Kelola Pengguna, Kelola Rekening Bank, Dashboard
        // Pembayaran). Server mengenali fetch() ini via header
        // X-Requested-With lalu balas HANYA fragmen HTML hasil (bukan
        // halaman penuh) — jadi Tailwind browser-CDN TIDAK perlu kompilasi
        // ulang CSS tiap pencarian, beda dengan submit form biasa.
        function initLiveSearch(opts) {
            const form = document.querySelector(opts.form);
            const results = document.querySelector(opts.results);
            if (!form || !results) return;

            let debounceTimer = null;

            function runSearch(url) {
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.text())
                    .then(html => {
                        results.innerHTML = html;
                        history.pushState(null, '', url);
                    })
                    .catch(() => { window.location = url; });
            }

            function buildUrl() {
                const params = new URLSearchParams(new FormData(form));
                return form.action + '?' + params.toString();
            }

            form.querySelectorAll('input[type="text"], input[type="search"]').forEach((input) => {
                input.addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => runSearch(buildUrl()), 1000);
                });
            });
            form.querySelectorAll('select').forEach((select) => {
                select.addEventListener('change', () => {
                    clearTimeout(debounceTimer);
                    runSearch(buildUrl());
                });
            });
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                clearTimeout(debounceTimer);
                runSearch(buildUrl());
            });

            // Cegat klik paginasi (di dalam <nav> bawaan Laravel) supaya
            // pindah halaman juga tanpa reload; tombol aksi per-baris
            // (lihat/edit/hapus dsb) di luar <nav> dibiarkan jalan normal.
            results.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (!link || !link.closest('nav[role="navigation"]')) return;
                e.preventDefault();
                runSearch(link.href);
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
