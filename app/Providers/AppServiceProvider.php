<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Jembatan supaya @can('proposals.manage'), @canany([...]), dan
        // Gate::allows('invoices.view') di seluruh aplikasi otomatis
        // mengecek lewat hasPermission() custom kita — TANPA perlu
        // mendaftarkan 13 Gate::define(...) satu per satu secara manual.
        // Gate::before dijalankan SEBELUM pengecekan ability normal;
        // return true/false di sini akan langsung dipakai sebagai hasil
        // akhir untuk ability apapun yang namanya cocok dengan pola
        // "{modul}.view" / "{modul}.manage" di tabel permissions.
            Gate::before(function ($user, string $ability) {
            // Hanya intercept ability yang memang berbentuk key permission
            // kita (mengandung titik, mis. "proposals.manage"). Ability
            // Laravel bawaan lain (kalau ada di masa depan) tidak terganggu.
            if (str_contains($ability, '.')) {
                return $user->hasPermission($ability);
            }

            return null; // biarkan Laravel lanjut ke pengecekan normal
        });
    }
}
