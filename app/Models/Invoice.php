<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    public const TYPE_DP        = 'DP';
    public const TYPE_PELUNASAN = 'Pelunasan';

    public const STATUS_UNPAID = 'Unpaid';
    public const STATUS_PAID   = 'Paid';

    protected $fillable = [
        'project_id',
        'invoice_number',
        'invoice_date',
        'invoice_type',
        'amount',
        'status',
        'payment_date',
        'term_description',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'invoice_date' => 'date',
        'payment_date' => 'date',
    ];
    /**
     * Tanggal yang ditampilkan di PDF Invoice sebagai "Tanggal Terbit".
     * Prioritas: invoice_date manual > created_at (fallback data lama).
     */
    public function getDisplayDateAttribute(): \Carbon\Carbon
    {
        return $this->invoice_date ?? $this->created_at;
    }
    
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
