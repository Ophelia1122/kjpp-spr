<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Helper utama untuk cek izin — dipakai di middleware permission
     * DAN langsung di Blade (mis. @if(auth()->user()->hasPermission('proposals.manage')))
     * untuk sembunyikan tombol yang tidak relevan bagi role user tersebut.
     */
    public function hasPermission(string $key): bool
    {
        if (!$this->role) {
            return false; // user belum punya role = tidak boleh akses apapun
        }

        return $this->role->hasPermission($key);
    }

    public function isAdministrator(): bool
    {
        return $this->role?->slug === Role::ADMINISTRATOR;
    }
}
