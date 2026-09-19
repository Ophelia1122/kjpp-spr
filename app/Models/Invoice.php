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
        'kwitansi_number',
        'invoice_date',
        'invoice_type',
        'amount',
        'percentage',
        'status',
        'payment_date',
        'term_description',
        'received_from_client_id',
        'on_behalf_of_client_id',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'percentage'   => 'decimal:2',
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

    /**
     * Umur invoice (hari kalender) sejak tanggal terbit — dipakai untuk
     * menandai tagihan tertunggak di Dashboard Pembayaran.
     */
    public function getAgeDaysAttribute(): int
    {
        return (int) $this->display_date->copy()->startOfDay()->diffInDays(now()->startOfDay());
    }

    /** "Telah diterima dari" yang dipilih di modal invoice (2026-09-14). */
    public function receivedFromClient()
    {
        return $this->belongsTo(Client::class, 'received_from_client_id');
    }

    /**
     * Pihak yang dicetak sebagai "Telah diterima Dari" (Invoice) & "Sudah
     * terima dari" (Kwitansi). Invoice lama tanpa pilihan -> Pemberi Tugas.
     */
    public function getPayerAttribute(): ?Client
    {
        return $this->receivedFromClient ?? optional($this->project)->instructingClient;
    }

    /** Pihak pada kalimat "Biaya Jasa Penilaian Properti an. …" (2026-09-14). */
    public function onBehalfOfClient()
    {
        return $this->belongsTo(Client::class, 'on_behalf_of_client_id');
    }

    /** Nama untuk "an. …" — invoice lama tanpa pilihan -> Pemberi Tugas. */
    public function getOnBehalfNameAttribute(): string
    {
        return (string) (optional($this->onBehalfOfClient)->client_name
            ?? optional(optional($this->project)->instructingClient)->client_name);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * =========================================================================
     * PECAH NOMINAL (GROSS) MENJADI FEE (NET) + PPN utk ditampilkan di
     * Invoice/Kwitansi — `amount` SELALU disimpan sbg angka gross (yang
     * benar-benar ditagihkan/diterima), lalu PPN dipisah balik dari situ:
     *   net = gross / (1 + tarif) ; ppn = gross - net
     * Rumus ini sama persis dipakai baik proyek fee_ppn_included true
     * maupun false — begitu sudah jadi satu angka gross utk TERMIN INI,
     * cara membalik pajaknya identik (lihat pembahasan di memori proyek).
     * =========================================================================
     */
    public function getPpnRateAttribute(): float
    {
        return (float) config('kjpp.ppn_rate', 0.11);
    }

    public function getNetAmountAttribute(): float
    {
        return round((float) $this->amount / (1 + $this->ppn_rate), 2);
    }

    public function getPpnAmountAttribute(): float
    {
        return round((float) $this->amount - $this->net_amount, 2);
    }
}
