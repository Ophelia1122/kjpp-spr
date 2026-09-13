<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * Dipakai di route sebagai: ->middleware('permission:proposals.manage')
     * Kalau user tidak punya izin tersebut -> 403, BUKAN redirect ke login
     * (karena user memang sudah login, cuma tidak berwenang untuk aksi ini).
     */
    public function handle(Request $request, Closure $next, string $permissionKey): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasPermission($permissionKey)) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
