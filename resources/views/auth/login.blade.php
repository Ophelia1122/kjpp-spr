<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — Internal Web-App KJPP</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
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

        <div class="rise rise-1 rounded-2xl border border-white/10 bg-white/[.06] p-6 shadow-2xl backdrop-blur-xl space-y-4">
            @if (session('error'))
                <div class="rounded-lg bg-red-500/15 border border-red-500/30 text-red-200 text-sm px-3 py-2">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="rounded-lg bg-emerald-500/15 border border-emerald-500/30 text-emerald-200 text-sm px-3 py-2">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-lg bg-red-500/15 border border-red-500/30 text-red-200 text-sm px-3 py-2">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('login.attempt') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           class="w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2.5 text-base text-white
                                  placeholder-transparent shadow-sm outline-none
                                  focus:border-blue-400/60 focus:bg-white/10 focus:ring-2 focus:ring-blue-500/30">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Password</label>
                    <input type="password" name="password" required autocomplete="current-password"
                           class="w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2.5 text-base text-white
                                  shadow-sm outline-none
                                  focus:border-blue-400/60 focus:bg-white/10 focus:ring-2 focus:ring-blue-500/30">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-400 select-none">
                    <input type="checkbox" name="remember" class="rounded border-white/20 bg-white/5 text-blue-500 focus:ring-blue-500/30">
                    Ingat saya di perangkat ini
                </label>
                <button type="submit"
                        class="w-full rounded-lg bg-blue-600 py-2.5 font-medium text-white shadow-lg shadow-blue-900/30
                               hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                    Masuk
                </button>
            </form>
        </div>

        <p class="rise rise-2 text-center text-xs text-slate-500 mt-6">
            &copy; {{ date('Y') }} KJPP Sugianto Prasodjo dan Rekan
        </p>
    </div>
</body>
</html>
