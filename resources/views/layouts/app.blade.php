<!DOCTYPE html>
<html lang="id" class="{{ auth()->check() && auth()->user()->dark_mode ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Internal Web-App KJPP')</title>
    @include('partials.favicon')
    {{-- C. Crossfade dari halaman login ke aplikasi (View Transitions, 2026-09-15).
         Hanya dipakai bila datang dari /login — navigasi antarhalaman aplikasi
         dilewati supaya tidak terasa lambat. --}}
    <style>
        @view-transition { navigation: auto; }
        /* Opsi 1: lewat gelap dulu (#020617) supaya perpindahan gelap <-> terang tidak silau. */
        ::view-transition { background: #020617; }
        ::view-transition-old(root) { animation: vt-dim-out .25s cubic-bezier(.4, 0, 1, 1) both; }
        ::view-transition-new(root) { animation: vt-dim-in .35s cubic-bezier(0, 0, .2, 1) .15s both; }
        @keyframes vt-dim-out { to { opacity: 0; } }
        @keyframes vt-dim-in { from { opacity: 0; } }

        /* Mode terang "kabut indigo" (2026-09-15, feedback user): latar #EEF1F8
           (lihat <body>), garis & latar tabel ikut bernuansa indigo tipis. */
        html:not(.dark) main .border-gray-200 { border-color: #DFE4F0; }
        html:not(.dark) main .border-gray-100 { border-color: #E8ECF5; }
        html:not(.dark) main .divide-gray-100 > :not(:last-child) { border-color: #E8ECF5; }
        html:not(.dark) main .bg-gray-50 { background-color: #F5F7FC; }

        /* Sakelar tema (partials/theme-switch): pentol matahari (terang) / bulan (gelap). */
        .theme-switch { position: relative; display: inline-block; width: 3.25rem; height: 1.75rem; flex-shrink: 0; border-radius: 9999px;
                        background: #1f2937; border: 1px solid rgba(148, 163, 184, .25); cursor: pointer;
                        transition: background-color .25s ease, border-color .25s ease; }
        .theme-switch:hover { border-color: rgba(148, 163, 184, .5); }
        .theme-switch:focus-visible { outline: 2px solid #818cf8; outline-offset: 2px; }
        .theme-switch[aria-checked="true"] { background: #312e81; border-color: rgba(129, 140, 248, .45); }
        .theme-switch .knob { position: absolute; top: 2px; left: 2px; display: grid; place-items: center; width: 1.375rem; height: 1.375rem;
                              border-radius: 9999px; background: #fcd34d; color: #92400e; box-shadow: 0 1px 3px rgba(0, 0, 0, .35);
                              transition: transform .3s cubic-bezier(.16, .84, .44, 1), background-color .3s ease, color .3s ease; }
        .theme-switch[aria-checked="true"] .knob { transform: translateX(1.5rem); background: #e0e7ff; color: #3730a3; }
        .theme-switch .knob svg { width: .875rem; height: .875rem; }
        .theme-switch .icon-moon, .theme-switch[aria-checked="true"] .icon-sun { display: none; }
        .theme-switch[aria-checked="true"] .icon-moon { display: block; }
    </style>
    <script>
        window.addEventListener('pagereveal', function (e) {
            if (e.viewTransition && !/\/login(\?|$)/.test(document.referrer ? new URL(document.referrer).pathname : '')) {
                e.viewTransition.skipTransition();
            }
        });
    </script>
    {{-- Tailwind browser v4.3.3 disimpan lokal (2026-09-14): jsdelivr sering lambat/diblokir operator seluler. --}}
    <script src="{{ asset('js/tailwindcss-browser.js') }}?v=4.3.3"></script>
    <style type="text/tailwindcss">
        @custom-variant dark (&:where(.dark, .dark *));
    </style>
    <style>
        /* ---- Transisi halus lintas fitur ---- */
        :root { --ease: cubic-bezier(.16,.84,.44,1); }

        /* Isi halaman muncul dengan fade + naik tipis tiap navigasi. */
        @keyframes appear { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        main > * { animation: appear .32s var(--ease) both; }

        /* Fokus keyboard terlihat jelas di seluruh aplikasi (2026-09-20, hasil
           audit UI). Hanya :focus-visible, jadi klik mouse tidak memunculkan
           cincin. Warna indigo sama dengan aksen header. */
        :focus-visible {
            outline: 2px solid #6366F1;
            outline-offset: 2px;
            border-radius: 4px;
        }
        html.dark :focus-visible { outline-color: #A5B4FC; }
        /* Input & select sudah punya ring bawaan Tailwind Forms; samakan warnanya. */
        input:focus-visible, select:focus-visible, textarea:focus-visible {
            outline-offset: 0;
        }

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

        /* ===== Modal global (2026-09-23, feedback user) =====
           1) Muncul halus: overlay memudar, kartu naik sedikit.
           2) Di layar lebar, overlay digeser selebar sidebar supaya kartu
              benar-benar di tengah AREA ISI, bukan di tengah layar. */
        @keyframes modalFade { from { opacity: 0; } to { opacity: 1; } }
        @keyframes modalPop  { from { opacity: 0; transform: translateY(8px) scale(.98); } to { opacity: 1; transform: none; } }
        div.fixed.inset-0.justify-center.flex { animation: modalFade .14s ease-out both; }
        div.fixed.inset-0.justify-center.flex > * { animation: modalPop .18s cubic-bezier(.16,.84,.44,1) both; }
        @media (min-width: 1024px) {
            div.fixed.inset-0.justify-center { padding-left: 16rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            div.fixed.inset-0.justify-center.flex,
            div.fixed.inset-0.justify-center.flex > * { animation: none; }
        }
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
<body class="bg-[#EEF1F8] dark:bg-gray-950">
    {{-- ===================== LAYAR SAMBUTAN SETELAH LOGIN (2026-09-15) =====================
         Muncul sekali setelah login (session flash "welcome"): layar gelap senada
         halaman login dengan logo & sapaan, lalu memudar memperlihatkan aplikasi —
         supaya perpindahan gelap -> terang tidak silau. CSS murni, dihapus dari DOM
         setelah selesai. --}}
    @if (session('welcome'))
        <div id="welcomeSplash" class="welcome-splash" aria-hidden="true">
            <div class="welcome-blob" style="top:-10rem;left:-8rem;background:rgba(37,99,235,.30)"></div>
            <div class="welcome-blob" style="bottom:-10rem;right:-6rem;background:rgba(99,102,241,.20)"></div>
            <div class="welcome-inner">
                <div class="welcome-logo">
                    <img src="{{ asset('images/logo-spr-icon.png') }}" alt="">
                    <span>SPR</span>
                </div>
                <p class="welcome-title">Selamat datang, {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}</p>
                <p class="welcome-sub">KJPP Sugianto Prasodjo &amp; Rekan</p>
            </div>
        </div>
        <style>
            .welcome-splash { position: fixed; inset: 0; z-index: 9999; display: grid; place-items: center; overflow: hidden; background: #020617;
                              animation: welcomeOut .55s cubic-bezier(.16,.84,.44,1) .95s forwards; }
            .welcome-blob { position: absolute; width: 24rem; height: 24rem; border-radius: 9999px; filter: blur(64px); pointer-events: none; }
            .welcome-inner { position: relative; text-align: center; color: #fff; animation: welcomeIn .5s cubic-bezier(.16,.84,.44,1) both; }
            .welcome-logo { display: flex; align-items: center; justify-content: center; gap: .5rem; }
            .welcome-logo img { height: 3rem; width: auto; }
            .welcome-logo span { font: italic 700 1.875rem/1 Georgia, 'Times New Roman', serif; letter-spacing: -.01em; }
            .welcome-title { margin-top: 1rem; font-size: 1.25rem; font-weight: 600; }
            .welcome-sub { margin-top: .2rem; font-size: .875rem; color: #94a3b8; }
            @keyframes welcomeIn { from { opacity: 0; transform: translateY(10px) scale(.98); } to { opacity: 1; transform: none; } }
            @keyframes welcomeOut { to { opacity: 0; visibility: hidden; } }
            @media (prefers-reduced-motion: reduce) {
                .welcome-inner { animation: none; }
                .welcome-splash { animation-duration: .01s; animation-delay: .7s; }
            }
        </style>
        <script>setTimeout(function () { var s = document.getElementById('welcomeSplash'); if (s) s.remove(); }, 1700);</script>
    @endif
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
                {{-- Logo + teks SPR, sama dengan halaman login (2026-09-14, feedback user). --}}
                <a href="{{ route('home') }}" class="block">
                    <span class="flex items-center gap-2">
                        <img src="{{ asset('images/logo-spr-icon.png') }}" alt="" class="h-9 w-auto">
                        <span class="text-2xl font-bold italic tracking-tight text-white" style="font-family: Georgia, 'Times New Roman', serif;">SPR</span>
                    </span>
                    <span class="mt-0.5 block text-xs text-gray-400">Supporting Business Unit Kebagusan</span>
                </a>
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
                        {{-- SPJ Surveyor: rekap survei per penilai (2026-09-19, feedback user). --}}
                        @can('survey.view')
                            <a href="{{ route('spj.index') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                    {{ request()->routeIs('spj.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/></svg> SPJ Surveyor
                            </a>
                        @endcan
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

                    @canany(['proposals.manage', 'roles.manage', 'users.manage', 'banks.manage', 'audit.view'])
                        <div class="pt-4 mt-4 border-t border-gray-800">
                            <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Pengaturan Sistem</p>

                            {{-- Sampah: paling atas di Pengaturan Sistem (2026-09-21, feedback user). --}}
                            @can('proposals.manage')
                                <a href="{{ route('trash.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                        {{ request()->routeIs('trash.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                    <svg aria-hidden="true" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg> Sampah
                                </a>
                            @endcan

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

                            @can('users.manage')
                                <a href="{{ route('settings.whatsapp.edit') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium
                                        {{ request()->routeIs('settings.whatsapp.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg> Bot WhatsApp
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
                        @include('partials.user-avatar', ['avatarUser' => auth()->user(), 'avatarClass' => 'h-9 w-9 bg-gray-700 text-sm group-hover:bg-blue-600'])
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

                        @include('partials.theme-switch', ['class' => 'justify-self-end'])
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
                <a href="{{ route('home') }}" class="flex items-center gap-1.5">
                    <img src="{{ asset('images/logo-spr-icon.png') }}" alt="" class="h-6 w-auto">
                    <span class="text-lg font-bold italic tracking-tight" style="font-family: Georgia, 'Times New Roman', serif;">SPR</span>
                </a>
                {{-- Ganti tema langsung dari header HP (2026-09-15, feedback user). --}}
                @include('partials.theme-switch', ['class' => 'ml-auto'])
            </header>

            <main class="flex-1 px-4 lg:px-8 py-6">
                @if (session('success'))
                    <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 text-sm px-4 py-3 flex items-start justify-between gap-3">
                        <span class="flex items-start gap-2"><svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>{{ session('success') }}</span>
                        <span class="flex shrink-0 items-center gap-3">
                            {{-- Aksi yang bisa dibatalkan memasang tombol "Urungkan"
                                 di sini (2026-09-25, feedback user), menggantikan
                                 modal konfirmasi sebelum aksi dijalankan. --}}
                            @if (session('undo'))
                                <form action="{{ session('undo')['url'] }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-md border border-green-300 px-2.5 py-1 text-xs font-medium text-green-700 hover:bg-green-100 dark:border-green-700 dark:text-green-300 dark:hover:bg-green-900/40">
                                        <svg aria-hidden="true" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg>
                                        {{ session('undo')['label'] ?? 'Urungkan' }}
                                    </button>
                                </form>
                            @endif
                            <button type="button" onclick="this.closest('div').remove()" class="text-green-500 hover:text-green-700 dark:hover:text-green-300 leading-none">&times;</button>
                        </span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 text-sm px-4 py-3 flex items-start justify-between gap-3">
                        <span class="flex items-start gap-2"><svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>{{ session('error') }}</span>
                        <button type="button" onclick="this.closest('div').remove()" class="text-red-500 hover:text-red-700 dark:hover:text-red-300 leading-none">&times;</button>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="mb-4 rounded-md bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-300 text-sm px-4 py-3 flex items-start justify-between gap-3">
                        <span class="flex items-start gap-2"><svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>{{ session('warning') }}</span>
                        <button type="button" onclick="this.closest('div').remove()" class="text-yellow-500 hover:text-yellow-700 dark:hover:text-yellow-300 leading-none">&times;</button>
                    </div>
                @endif

                @if (session('info'))
                    <div class="mb-4 rounded-md bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-300 text-sm px-4 py-3 flex items-start justify-between gap-3">
                        <span class="flex items-start gap-2"><svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>{{ session('info') }}</span>
                        <button type="button" onclick="this.closest('div').remove()" class="text-blue-500 hover:text-blue-700 dark:hover:text-blue-300 leading-none">&times;</button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 text-sm px-4 py-3">
                        <p class="font-semibold mb-1">Terjadi kesalahan input:</p>
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


    {{-- Semua modal: klik di luar kartu atau tekan Esc untuk menutup
         (2026-09-23, feedback user). Modal = div.fixed.inset-0 yang dibuka
         dengan menambah kelas "flex". --}}
    <script>
        (function () {
            function isModal(el) {
                return el instanceof HTMLElement
                    && el.classList.contains('fixed') && el.classList.contains('inset-0')
                    && el.classList.contains('flex') && el.classList.contains('justify-center');
            }
            function close(el) {
                el.classList.add('hidden');
                el.classList.remove('flex');
            }
            document.addEventListener('mousedown', function (e) {
                if (isModal(e.target)) {
                    close(e.target);
                }
            });
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                document.querySelectorAll('div.fixed.inset-0.flex.justify-center').forEach(close);
            });
        })();
    </script>

    {{-- ===================== MODAL KONFIRMASI GLOBAL (2026-09-15) =====================
         Menggantikan dialog confirm() bawaan browser. Pakai: <form data-confirm="Pesan">. --}}
    <div id="confirmModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
        <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800">
            <div class="flex gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 id="confirmTitle" class="text-base font-semibold text-gray-900 dark:text-gray-100">Konfirmasi</h2>
                    <p id="confirmMessage" class="mt-1 text-sm text-gray-600 dark:text-gray-300"></p>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" id="confirmCancel" class="inline-flex h-[38px] items-center justify-center gap-1.5 rounded-md border border-gray-300 px-4 text-sm font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/60">Batal</button>
                <button type="button" id="confirmOk" class="px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">Ya, lanjutkan</button>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const modal = document.getElementById('confirmModal');
            let pendingForm = null;
            const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); pendingForm = null; };
            // Tangkap di fase capture supaya handler lain (mis. AJAX) tidak jalan sebelum dikonfirmasi.
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (!form.dataset || !form.dataset.confirm || form.dataset.confirmed) return;
                e.preventDefault();
                e.stopImmediatePropagation();
                pendingForm = form;
                document.getElementById('confirmMessage').textContent = form.dataset.confirm;
                const destructive = /hapus|batal|kembalikan/i.test(form.dataset.confirm);
                document.getElementById('confirmOk').className = 'px-4 py-2 text-sm font-medium rounded-md text-white '
                    + (destructive ? 'bg-rose-600 hover:bg-rose-700' : 'bg-blue-600 hover:bg-blue-700');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.getElementById('confirmOk').focus();
            }, true);
            document.getElementById('confirmOk').addEventListener('click', () => {
                const form = pendingForm;
                close();
                if (!form) return;
                form.dataset.confirmed = '1';
                form.requestSubmit ? form.requestSubmit() : form.submit();
                delete form.dataset.confirmed;
            });
            document.getElementById('confirmCancel').addEventListener('click', close);
            modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && pendingForm) close(); });
        })();

        // ===================== CEGAH KLIK GANDA (2026-09-15) =====================
        // Form POST yang benar-benar dikirim (tidak dicegat AJAX/konfirmasi) dikunci
        // sampai halaman berganti: kiriman kedua diabaikan & tombolnya dinonaktifkan.
        (function () {
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (e.defaultPrevented || (form.method || '').toLowerCase() !== 'post') return;
                if (form.dataset.submitting) { e.preventDefault(); return; }
                form.dataset.submitting = '1';
                const buttons = [...form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]')];
                if (form.id) buttons.push(...document.querySelectorAll(`button[form="${form.id}"]`));
                // Ditunda supaya nilai tombol (name/value) tetap ikut terkirim.
                setTimeout(() => buttons.forEach((b) => {
                    b.disabled = true;
                    b.classList.add('opacity-60', 'cursor-wait');

                    // Tombol berlabel teks diganti "Menyimpan…" + lingkaran
                    // berputar (2026-09-25, feedback user) supaya jelas
                    // permintaannya sedang berjalan. Tombol ikon dibiarkan.
                    const label = (b.textContent || '').trim();
                    if (! label || b.dataset.busyDone) return;
                    b.dataset.busyDone = '1';
                    b.dataset.labelAsli = b.innerHTML;
                    b.innerHTML = '<span class="inline-flex items-center gap-1.5">'
                        + '<span class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-r-transparent"></span>'
                        + (/hapus|batal|kosongkan/i.test(label) ? 'Memproses…' : 'Menyimpan…')
                        + '</span>';
                }), 0);
            });
            // Kembali lewat tombol Back (bfcache) -> form bisa dipakai lagi.
            window.addEventListener('pageshow', (e) => {
                if (!e.persisted) return;
                document.querySelectorAll('form[data-submitting]').forEach((f) => {
                    delete f.dataset.submitting;
                    f.querySelectorAll('button, input[type="submit"]').forEach((b) => {
                        b.disabled = false;
                        b.classList.remove('opacity-60', 'cursor-wait');
                        if (b.dataset.labelAsli) {
                            b.innerHTML = b.dataset.labelAsli;
                            delete b.dataset.labelAsli;
                            delete b.dataset.busyDone;
                        }
                    });
                });
            });
        })();
    </script>

    <script>
        function openSidebar() {
            document.getElementById('sidebar').classList.remove('-translate-x-full');
            document.getElementById('sidebarOverlay').classList.remove('hidden');
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.add('-translate-x-full');
            document.getElementById('sidebarOverlay').classList.add('hidden');
        }

        // Semua sakelar tema (sidebar & header HP) ikut disinkronkan.
        function toggleTheme() {
            const isDark = !document.documentElement.classList.contains('dark');
            document.documentElement.classList.toggle('dark', isDark);
            document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
                btn.setAttribute('aria-checked', isDark ? 'true' : 'false');
                btn.setAttribute('aria-label', isDark ? 'Ganti ke mode terang' : 'Ganti ke mode gelap');
            });
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
                // Tandai sedang memuat (2026-09-20, hasil audit UI): tanpa ini
                // jeda 1 detik terasa seperti aplikasi diam.
                results.style.opacity = '.45';
                results.setAttribute('aria-busy', 'true');
                results.style.pointerEvents = 'none';

                const done = () => {
                    results.style.opacity = '';
                    results.style.pointerEvents = '';
                    results.removeAttribute('aria-busy');
                };

                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.text())
                    .then(html => {
                        results.innerHTML = html;
                        history.pushState(null, '', url);
                        done();
                    })
                    .catch(() => { done(); window.location = url; });
            }

            function buildUrl() {
                const params = new URLSearchParams(new FormData(form));
                return form.action + '?' + params.toString();
            }

            form.querySelectorAll('input[type="text"], input[type="search"]').forEach((input) => {
                // Jeda 1 detik terasa lambat; 300 ms sudah cukup menahan
                // permintaan beruntun (2026-09-25, feedback user).
                input.addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => runSearch(buildUrl()), 300);
                });
                // Esc mengosongkan pencarian dan langsung memuat ulang daftar.
                input.addEventListener('keydown', (e) => {
                    if (e.key !== 'Escape' || ! input.value) return;
                    e.preventDefault();
                    input.value = '';
                    clearTimeout(debounceTimer);
                    runSearch(buildUrl());
                });
            });
            // Tanggal ikut memicu pencarian — tombol "Terapkan Filter" sudah
            // dihapus (2026-09-14, feedback user).
            form.querySelectorAll('select, input[type="date"]').forEach((select) => {
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

        // ---------- Pintasan papan tik (2026-09-24, feedback user) ----------
        // "/" ke kolom cari, "n" proposal baru, "?" daftar pintasan.
        // Diabaikan saat sedang mengetik atau saat modal terbuka.
        (function () {
            const bisaBuatProposal = @json(auth()->check() && auth()->user()->can('proposals.manage'));
            const urlProposalBaru  = @json(auth()->check() && auth()->user()->can('proposals.manage') ? route('proposals.create') : null);

            function sedangMengetik(el) {
                return el && (el.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName));
            }

            function tampilkanBantuan() {
                let box = document.getElementById('shortcutHelp');
                if (box) { box.remove(); return; }

                box = document.createElement('div');
                box.id = 'shortcutHelp';
                box.className = 'fixed bottom-4 left-1/2 z-[80] -translate-x-1/2 rounded-lg bg-gray-900 px-4 py-3 text-xs text-white shadow-xl';
                box.innerHTML = '<b class="mb-1 block text-[11px] uppercase tracking-wide text-gray-400">Pintasan</b>'
                    + '<div><kbd class="rounded bg-gray-700 px-1.5">/</kbd> cari'
                    + (bisaBuatProposal ? ' &middot; <kbd class="rounded bg-gray-700 px-1.5">n</kbd> proposal baru' : '')
                    + ' &middot; <kbd class="rounded bg-gray-700 px-1.5">?</kbd> tutup bantuan ini</div>';
                document.body.appendChild(box);
                setTimeout(() => box.remove(), 6000);
            }

            document.addEventListener('keydown', function (e) {
                if (e.ctrlKey || e.metaKey || e.altKey || sedangMengetik(document.activeElement)) return;
                // Modal terbuka: jangan ganggu.
                if (document.querySelector('.fixed.inset-0.flex:not(.hidden)')) return;

                if (e.key === '/') {
                    const cari = document.querySelector('input[name="q"], input[type="search"]');
                    if (cari) { e.preventDefault(); cari.focus(); cari.select(); }
                    return;
                }
                if (e.key === 'n' && urlProposalBaru) {
                    e.preventDefault();
                    window.location.href = urlProposalBaru;
                    return;
                }
                if (e.key === '?') {
                    e.preventDefault();
                    tampilkanBantuan();
                }
            });
        })();

        // ---------- Menu tarik-turun [data-dropdown] (2026-09-14) ----------
        // Saat dibuka, menu DIPINDAH ke <body> dan diposisikan `fixed` terhadap
        // layar, lalu dikembalikan ke tempatnya saat ditutup. Alasannya:
        //  - kartu memakai transform (animasi `main > *` & `.lift:hover`), dan
        //    elemen ber-transform membuat `fixed` di dalamnya relatif ke kartu,
        //    sehingga menu terlempar ke luar layar (bug "tombol unduh tidak
        //    berfungsi", 2026-09-14);
        //  - tabel overflow-x-auto memotong menu absolut di baris terbawah.
        // Delegasi di document, jadi tetap jalan untuk isi hasil live search.
        (function () {
            function closeAll() {
                document.querySelectorAll('[data-dropdown-menu]').forEach(function (menu) {
                    if (menu.style.display === 'none') return;
                    menu.style.display = 'none';
                    if (menu._home) {
                        menu._home.appendChild(menu);
                        menu._home = null;
                    }
                    if (menu._toggle) menu._toggle.setAttribute('aria-expanded', 'false');
                });
            }

            document.addEventListener('click', function (e) {
                const toggle = e.target.closest('[data-dropdown-toggle]');
                if (toggle) {
                    e.preventDefault();
                    const menu = toggle._menu || toggle.closest('[data-dropdown]').querySelector('[data-dropdown-menu]');
                    toggle._menu = menu;
                    const wasOpen = menu.style.display !== 'none';
                    closeAll();
                    if (wasOpen) return;

                    menu._toggle = toggle;
                    menu._home = menu.parentNode;
                    document.body.appendChild(menu);
                    menu.style.position = 'fixed';
                    menu.style.display = 'block';
                    const r = toggle.getBoundingClientRect();
                    let top = r.bottom + 4;
                    if (top + menu.offsetHeight > window.innerHeight - 8) {
                        top = Math.max(8, r.top - menu.offsetHeight - 4);
                    }
                    menu.style.top = top + 'px';
                    menu.style.left = Math.max(8, Math.min(r.right - menu.offsetWidth, window.innerWidth - menu.offsetWidth - 8)) + 'px';
                    toggle.setAttribute('aria-expanded', 'true');
                    return;
                }

                // Pilih item menu -> tutup menu (modal/konfirmasi tetap jalan).
                if (e.target.closest('[data-dropdown-menu] [role="menuitem"]')) {
                    setTimeout(closeAll, 0);
                    return;
                }
                if (!e.target.closest('[data-dropdown-menu]')) closeAll();
            });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(); });
            window.addEventListener('scroll', function () { closeAll(); }, true);
            window.addEventListener('resize', function () { closeAll(); });
        })();
    </script>
    @auth
        @include('partials.quick-palette')
    @endauth
    @stack('scripts')
</body>
</html>
