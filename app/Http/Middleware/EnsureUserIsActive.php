<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * User yang di-nonaktifkan (mis. karyawan resign) langsung ter-logout
     * paksa di request berikutnya, meski sesinya masih aktif di browser.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Akun Anda telah dinonaktifkan. Hubungi Administrator.');
        }

        return $next($request);
    }
}
