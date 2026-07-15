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
        'invoice_type',
        'amount',
        'status',
        'payment_date',
        'term_description',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
