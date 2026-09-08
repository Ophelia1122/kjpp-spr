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

        {{-- ===================== OVERLAY (mobile, klik untuk tutup sidebar) ===================== --}}
        <div id="sidebarOverlay" onclick="closeSidebar()"
             class="hidden fixed inset-0 bg-black/50 z-30 lg:hidden"></div>

        {{-- ===================== SIDEBAR ===================== --}}
        {{-- Desktop: selalu tampil (lg:translate-x-0). Mobile: default
             tersembunyi di luar layar (-translate-x-full), digeser masuk
             lewat JS toggleSidebar() saat tombol hamburger diklik. --}}
        <aside id="sidebar"
               class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-gray-900 text-gray-100 flex flex-col
                      transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out">
            <div class="px-6 py-5 border-b border-gray-800 flex items-center justify-between">
                <div>
                    <div class="text-lg font-bold text-white">KJPP SPR</div>
                    <div class="text-xs text-gray-400">Internal Web-App</div>
                </div>
                <button onclick="closeSidebar()" class="lg:hidden text-gray-400 hover:text-white text-xl leading-none">&times;</button>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                @auth
                    @can('dashboard.view')
                        <a href="{{ route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <span>📊</span> Dashboard Proyek
                        </a>
                    @endcan

                    @can('proposals.manage')
                        <a href="{{ route('proposals.create') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                  {{ request()->routeIs('proposals.create') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <span>📝</span> Buat Proposal Baru
                        </a>
                    @endcan

                    @can('clients.view')
                        <a href="{{ route('clients.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                  {{ request()->routeIs('clients.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <span>👥</span> Daftar Klien
                        </a>
                    @endcan

                    @canany(['roles.manage', 'users.manage', 'audit.view'])
                        <div class="pt-4 mt-4 border-t border-gray-800">
                            <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Pengaturan Sistem</p>

                            @can('users.manage')
                                <a href="{{ route('users.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                        {{ request()->routeIs('users.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                    <span>👤</span> Kelola Pengguna
                                </a>
                            @endcan

                            @can('roles.manage')
                                <a href="{{ route('roles.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                        {{ request()->routeIs('roles.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                    <span>🔐</span> Kelola Role & Izin
                                </a>
                            @endcan

                            @can('audit.view')
                                <a href="{{ route('audit.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                        {{ request()->routeIs('audit.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                    <span>📜</span> Log Aktivitas
                                </a>
                            @endcan
                        </div>
                    @endcanany
                @endauth
            </nav>

            {{-- ===================== INFO USER + LOGOUT ===================== --}}
             @auth
                <div class="px-4 py-4 border-t border-gray-800">
                    <div class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-gray-400 mb-3">{{ auth()->user()->role->name ?? '-' }}</div>
                    <a href="{{ route('profile.edit') }}" class="block text-sm text-gray-300 hover:text-white mb-2">
                        🔑 Ganti Password
                    </a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full text-left text-sm text-red-400 hover:text-red-300">
                            🚪 Keluar
                        </button>
                    </form>
                </div>
            @endauth
        </aside>

        {{-- ===================== MAIN CONTENT ===================== --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- Topbar — SEKARANG cuma tombol hamburger + judul, bukan
                 daftar link cramped seperti sebelumnya. Semua navigasi
                 tetap dari sidebar yang sama (di-toggle di mobile). --}}
            <header class="lg:hidden bg-gray-900 text-white px-4 py-3 flex items-center gap-3 sticky top-0 z-20">
                <button onclick="openSidebar()" class="text-2xl leading-none" aria-label="Buka menu">☰</button>
                <div class="font-bold">KJPP SPR</div>
            </header>

            <main class="flex-1 px-4 lg:px-8 py-6">
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

    <script>
        function openSidebar() {
            document.getElementById('sidebar').classList.remove('-translate-x-full');
            document.getElementById('sidebarOverlay').classList.remove('hidden');
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.add('-translate-x-full');
            document.getElementById('sidebarOverlay').classList.add('hidden');
        }
    </script>
</body>
</html>
