<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — Internal Web-App KJPP</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <div class="text-2xl font-bold text-white">KJPP SPR</div>
            <div class="text-sm text-gray-400">Internal Web-App</div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 space-y-4">
            @if (session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 text-red-800 text-sm px-3 py-2">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 text-green-800 text-sm px-3 py-2">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 text-red-800 text-sm px-3 py-2">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.attempt') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-base"
                           placeholder="nama@kjpp-spr.co.id">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" required
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-base">
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remember" class="rounded border-gray-300">
                    Ingat saya di perangkat ini
                </label>
                <button type="submit"
                        class="w-full py-2.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">
                    Masuk
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-gray-500 mt-6">
            &copy; {{ date('Y') }} KJPP Sugianto Prasodjo dan Rekan
        </p>
    </div>
</body>
</html>
