<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cookie "Ingat saya di perangkat ini" bawaan Laravel bertahan 5 tahun,
 * jadi admin tidak pernah ter-logout walau PC dimatikan (2026-10-02,
 * keluhan admin). Kolom users.remember_token_issued_at dicatat saat login
 * dengan centang "Ingat saya" (lihat LoginController); middleware ini
 * memaksa login ulang kalau sudah lebih dari 24 jam sejak itu, meski
 * cookie-nya sendiri masih berlaku.
 *
 * Auth::viaRemember() hanya true pada request yang login-nya berasal dari
 * cookie recaller (bukan dari submit form login atau sesi aktif biasa),
 * jadi login manual yang masih berjalan dalam 1 sesi tidak kena batas ini.
 */
class EnsureRememberNotExpired
{
    private const MAX_JAM = 24;

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::viaRemember()) {
            $user = $request->user();
            $issuedAt = $user?->remember_token_issued_at;

            if (! $issuedAt || $issuedAt->diffInHours(now()) >= self::MAX_JAM) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Sesi "Ingat saya" sudah lewat 24 jam — silakan login ulang.');
            }
        }

        return $next($request);
    }
}
