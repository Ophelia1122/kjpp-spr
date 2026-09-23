<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Login memakai USERNAME (2026-09-23, feedback user). Email tetap disimpan
 * untuk data & pemulihan akun. Username diisi otomatis dari bagian depan
 * email; bila kembar, ditambah angka. Hanya MENAMBAH kolom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('name');
        });

        $taken = [];
        foreach (DB::table('users')->select('id', 'email')->get() as $user) {
            $base = Str::of((string) $user->email)->before('@')->lower()->replaceMatches('/[^a-z0-9._-]/', '')->value();
            $base = $base !== '' ? $base : 'user' . $user->id;

            $username = $base;
            $i = 1;
            while (in_array($username, $taken, true) || DB::table('users')->where('username', $username)->exists()) {
                $username = $base . (++$i);
            }
            $taken[] = $username;

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
