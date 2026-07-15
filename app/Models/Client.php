<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_name',
        'client_type',
        'address',
        'contact_person',
        'phone',
        'email',
    ];

    /**
     * Proyek-proyek di mana klien ini berperan sebagai Pemberi Tugas.
     */
    public function projectsAsInstructingClient()
    {
        return $this->hasMany(Project::class, 'instructing_client_id');
    }

    /**
     * Proyek-proyek di mana klien ini berperan sebagai Pengguna Laporan
     * (many-to-many lewat pivot project_intended_users).
     */
    public function projectsAsIntendedUser()
    {
        return $this->belongsToMany(
            Project::class,
            'project_intended_users',
            'client_id',
            'project_id'
        )->withTimestamps();
    }
}
