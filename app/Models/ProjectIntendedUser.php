<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectIntendedUser extends Model
{
    protected $fillable = [
        'project_id',
        'client_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
