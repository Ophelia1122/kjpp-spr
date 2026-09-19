<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris = satu orang di daftar "Adapun petugas kami" pada Surat
 * Tugas suatu proyek. Jabatan/No. MAPPI yang tercetak SELALU diambil
 * langsung dari biodata `users` (lihat relasi user()) — tabel ini hanya
 * menyimpan "siapa" dan urutannya, supaya tidak ada sumber data ganda.
 */
class ProjectAssignmentStaff extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'sort_order',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
