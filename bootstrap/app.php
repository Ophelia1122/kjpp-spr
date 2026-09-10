<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'active'     => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);

        // Terapkan pengecekan akun aktif ke SEMUA request yang sudah
        // login (bukan cuma route tertentu), supaya user yang baru saja
        // dinonaktifkan langsung ter-logout di request berikutnya.
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureUserIsActive::class);
    })
    
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // CSRF token kedaluwarsa (mis. halaman login dibiarkan terbuka
        // lama lalu di-submit, atau sesi habis di tengah kerja). Jangan
        // tampilkan halaman "419 Page Expired" yang buntu — kembalikan ke
        // halaman sebelumnya (form yang sama) dengan token segar + pesan
        // jelas, input non-sensitif dipertahankan. Catatan: di Laravel 13
        // TokenMismatchException sudah dikonversi ke HttpException(419)
        // sebelum render callback, jadi cocokkan lewat status code.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null; // bukan kasus CSRF — biarkan handler default
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.'], 419);
            }

            return redirect()->to($request->headers->get('referer') ?: route('login'))
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->with('error', 'Sesi Anda kedaluwarsa. Silakan coba lagi.');
        });
    })->create();
