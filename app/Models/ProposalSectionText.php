<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris = override teks baku SATU bab proposal untuk SATU proyek.
 * Lihat migration 2024_01_11_000001 & App\Services\ProposalDocxBuilder.
 */
class ProposalSectionText extends Model
{
    protected $fillable = [
        'project_id',
        'section_key',
        'body',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
