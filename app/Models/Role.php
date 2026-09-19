<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    // Slug baku 4 role inti — dipakai di berbagai tempat (seeder, User
    // model, view) supaya tidak ada string literal berulang-ulang yang
    // gampang typo.
    public const ADMINISTRATOR   = 'administrator';
    public const ADMIN_PRODUKSI  = 'admin-produksi';
    public const ADMIN_KEUANGAN  = 'admin-keuangan';
    public const SURVEYOR        = 'surveyor';

    protected $fillable = ['name', 'slug', 'is_system'];

    protected $casts = ['is_system' => 'boolean'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function hasPermission(string $key): bool
    {
        // Administrator SELALU punya semua izin, tidak peduli isi tabel
        // permission_role — ini pengaman supaya admin tidak bisa
        // mengunci dirinya sendiri lewat kesalahan di UI Role Management.
        if ($this->slug === self::ADMINISTRATOR) {
            return true;
        }

        return $this->permissions->contains('key', $key);
    }
}
