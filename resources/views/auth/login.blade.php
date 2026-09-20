<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — Internal Web-App KJPP</title>
    @include('partials.favicon')
    {{-- Tailwind browser v4.3.3 disimpan lokal (2026-09-14): jsdelivr sering lambat/diblokir operator seluler. --}}
    <script src="{{ asset('js/tailwindcss-browser.js') }}?v=4.3.3"></script>
    <style>
        :root { --ease: cubic-bezier(.16,.84,.44,1); }
        @keyframes rise { from { opacity: 0; transform: translateY(18px) scale(.98); } to { opacity: 1; transform: none; } }
        @keyframes glow { 0%,100% { opacity: .35; } 50% { opacity: .6; } }
        .rise { animation: rise .5s var(--ease) both; }
        .rise-1 { animation-delay: .06s; }
        .rise-2 { animation-delay: .12s; }
        input, button, a { transition: background-color .16s ease, border-color .16s ease, box-shadow .18s ease, color .16s ease, transform .12s var(--ease); }
        button:active { transform: translateY(1px) scale(.99); }
        .blob { animation: glow 7s ease-in-out infinite; }

        /* Kolom input: soft border slate #1E293B + inner glow tipis. */
        /* Garis tepi dibuat lebih terang (#1E293B -> #334155, 2026-09-15, feedback user). */
        .field { background: rgba(15, 23, 42, .55); border: 1px solid #334155; box-shadow: inset 0 1px 0 rgba(148, 163, 184, .06); }
        .field:hover { border-color: #475569; }
        .field:focus { border-color: rgba(129, 140, 248, .45); background: rgba(15, 23, 42, .75); box-shadow: inset 0 1px 0 rgba(148, 163, 184, .06), 0 0 0 4px rgba(99, 102, 241, .10); }
        /* Autofill browser jangan memutihkan kolom. */
        .field:-webkit-autofill { -webkit-text-fill-color: #fff; caret-color: #fff; box-shadow: inset 0 0 0 1000px #0f172a; transition: background-color 9999s; }

        /* Floating label: di tengah kolom saat kosong, naik & mengecil saat fokus/berisi. */
        .float-label { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); pointer-events: none;
                       font-size: .95rem; color: #64748b; transition: top .18s var(--ease), font-size .18s var(--ease), color .18s ease, transform .18s var(--ease); }
        .field:focus + .float-label,
        .field:not(:placeholder-shown) + .float-label,
        .field:-webkit-autofill + .float-label { top: .45rem; transform: none; font-size: .7rem; letter-spacing: .02em; }
        .field:focus + .float-label { color: #a5b4fc; }
        .field:not(:focus):not(:placeholder-shown) + .float-label,
        .field:-webkit-autofill + .float-label { color: #94a3b8; }

        /* Kartu form: garis tepi gradien indigo yang menyala lembut & berdenyut
           pelan (2026-09-15, feedback user). Border digambar lewat ::before + mask
           supaya gradiennya hanya di garis, bukan di isi kartu. */
        @keyframes cardGlow {
            0%, 100% { box-shadow: 0 0 34px -12px rgba(99, 102, 241, .40), 0 25px 50px -12px rgba(0, 0, 0, .6); }
            50%      { box-shadow: 0 0 46px -8px rgba(99, 102, 241, .60), 0 25px 50px -12px rgba(0, 0, 0, .6); }
        }
        .glow-card { position: relative; animation: rise .5s var(--ease) .06s both, cardGlow 5s ease-in-out .6s infinite; }
        .glow-card::before {
            content: ""; position: absolute; inset: 0; border-radius: inherit; padding: 1px; pointer-events: none;
            background: linear-gradient(140deg, rgba(165, 180, 252, .70), rgba(99, 102, 241, .18) 35%, rgba(56, 189, 248, .10) 60%, rgba(129, 140, 248, .60));
            -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite: xor; mask-composite: exclude;
        }

        /* A. Spinner kecil di tombol Masuk. */
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner { width: 1rem; height: 1rem; border-radius: 9999px; border: 2px solid rgba(255, 255, 255, .3); border-top-color: #fff; animation: spin .7s linear infinite; }
        .btn-login:disabled { cursor: progress; }

        /* B. Saat login diproses, logo & kartu meredup dan sedikit mengecil
           (memakai animation karena .rise/.glow-card sudah beranimasi). */
        @keyframes leave { to { opacity: .55; transform: translateY(-6px) scale(.98); } }
        body.leaving .rise { animation: leave .35s var(--ease) forwards; }

        /* C. Transisi antarhalaman bawaan browser (View Transitions). Browser yang
           belum mendukung mengabaikannya. Pasangannya ada di layouts/app.blade.php. */
        @view-transition { navigation: auto; }
        /* Opsi 1 (2026-09-15): "fade through dark" — halaman lama meredup ke latar
           gelap dulu, baru halaman baru muncul. Juga dipakai saat logout (terang -> login). */
        ::view-transition { background: #020617; }
        ::view-transition-old(root) { animation: vt-dim-out .25s cubic-bezier(.4, 0, 1, 1) both; }
        ::view-transition-new(root) { animation: vt-dim-in .35s cubic-bezier(0, 0, .2, 1) .15s both; }
        @keyframes vt-dim-out { to { opacity: 0; } }
        @keyframes vt-dim-in { from { opacity: 0; } }

        /* Tombol Masuk: indigo redup, glow halus saat hover. */
        .btn-login { background: #3F4A8A; border: 1px solid rgba(129, 140, 248, .18); transition: background-color .2s ease, box-shadow .25s ease, transform .12s var(--ease); }
        .btn-login:hover { background: #4A56A0; box-shadow: 0 0 22px -4px rgba(99, 102, 241, .55); }
        .btn-login:focus-visible { box-shadow: 0 0 0 3px rgba(99, 102, 241, .35); }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: .001ms !important; transition-duration: .001ms !important; } }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center px-4 overflow-hidden">

    {{-- Latar dekoratif --}}
    <div class="blob pointer-events-none absolute -top-40 -left-32 h-96 w-96 rounded-full bg-blue-600/30 blur-3xl"></div>
    <div class="blob pointer-events-none absolute -bottom-40 -right-24 h-96 w-96 rounded-full bg-indigo-500/20 blur-3xl" style="animation-delay:2s"></div>

    <div class="relative w-full max-w-sm">
        <div class="rise text-center mb-7">
            <div class="mx-auto mb-3 flex items-center justify-center gap-2">
                <img src="{{ asset('images/logo-spr-icon.png') }}" alt="" class="h-13 w-auto">
                <span class="text-3xl font-bold italic tracking-tight text-white" style="font-family: Georgia, 'Times New Roman', serif;">SPR</span>
            </div>
            <div class="text-lg font-semibold text-white">KJPP Sugianto Prasodjo &amp; Rekan</div>
            <div class="text-sm text-slate-400">Workshop Kebagusan</div>
        </div>

        <div class="glow-card rise rise-1 rounded-2xl bg-white/[.06] p-6 backdrop-blur-xl space-y-4">
            @if (session('error'))
                <div class="rounded-lg bg-red-500/15 border border-red-500/30 text-red-200 text-sm px-3 py-2">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="rounded-lg bg-emerald-500/15 border border-emerald-500/30 text-emerald-200 text-sm px-3 py-2">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-lg bg-red-500/15 border border-red-500/30 text-red-200 text-sm px-3 py-2">{{ $errors->first() }}</div>
            @endif

            {{-- Floating label + soft border slate (#1E293B) + tombol indigo redup
                 dengan glow halus saat hover (2026-09-15, feedback user). Label
                 naik saat kolom difokus / berisi (:placeholder-shown, placeholder=" "). --}}
            <form id="loginForm" action="{{ route('login.attempt') }}" method="POST" class="space-y-4">
                @csrf
                <div class="relative">
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder=" "
                           class="field peer w-full rounded-lg px-3 pt-5 pb-2 text-base text-white outline-none">
                    <label for="email" class="float-label">Email</label>
                </div>
                <div class="relative">
                    <input type="password" id="password" name="password" required autocomplete="current-password" placeholder=" "
                           class="field peer w-full rounded-lg pl-3 pr-11 pt-5 pb-2 text-base text-white outline-none">
                    <label for="password" class="float-label">Password</label>
                    <button type="button" id="togglePassword" aria-label="Tampilkan password" aria-pressed="false"
                            class="absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-md text-slate-500 hover:text-slate-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40">
                        <svg aria-hidden="true" data-eye="show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                        <svg aria-hidden="true" data-eye="hide" class="h-5 w-5" style="display:none" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                    </button>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-400 select-none">
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded accent-indigo-500 [color-scheme:dark]">
                    Ingat saya di perangkat ini
                </label>
                {{-- A. Loading state saat login diproses (2026-09-15, feedback user). --}}
                <button type="submit" id="loginBtn" class="btn-login flex w-full items-center justify-center gap-2 rounded-lg py-2.5 font-medium text-white focus:outline-none">
                    <span data-state="idle">Masuk</span>
                    <span data-state="busy" class="items-center gap-2" style="display:none"><span class="spinner" aria-hidden="true"></span>Memproses…</span>
                </button>
            </form>
            <script>
                (function () {
                    var btn = document.getElementById('togglePassword');
                    var input = document.getElementById('password');
                    btn.addEventListener('click', function () {
                        var show = input.type === 'password';
                        input.type = show ? 'text' : 'password';
                        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                        btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
                        btn.querySelector('[data-eye="show"]').style.display = show ? 'none' : '';
                        btn.querySelector('[data-eye="hide"]').style.display = show ? '' : 'none';
                        input.focus();
                    });

                    // A + B: tombol loading & halaman login memudar selama server
                    // memproses. Event submit hanya jalan kalau isian valid.
                    var form = document.getElementById('loginForm');
                    var loginBtn = document.getElementById('loginBtn');
                    function setBusy(busy) {
                        loginBtn.querySelector('[data-state="idle"]').style.display = busy ? 'none' : '';
                        loginBtn.querySelector('[data-state="busy"]').style.display = busy ? 'inline-flex' : 'none';
                        loginBtn.setAttribute('aria-busy', busy ? 'true' : 'false');
                        loginBtn.disabled = busy;
                        document.body.classList.toggle('leaving', busy);
                        if (! busy) delete form.dataset.busy;
                    }
                    form.addEventListener('submit', function (e) {
                        if (form.dataset.busy) { e.preventDefault(); return; }
                        form.dataset.busy = '1';
                        setTimeout(function () { setBusy(true); }, 0);
                    });
                    // Kembali lewat tombol Back (bfcache) -> tombol normal lagi.
                    window.addEventListener('pageshow', function (e) { if (e.persisted) setBusy(false); });
                })();
            </script>
        </div>

        <p class="rise rise-2 text-center text-xs text-slate-500 mt-6">
            &copy; {{ date('Y') }} KJPP Sugianto Prasodjo dan Rekan - Xyro
        </p>
    </div>
</body>
</html>
