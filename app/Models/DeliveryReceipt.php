<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tanda Terima Pengiriman Buku (2026-09-23, feedback user).
 * Nomor mengikuti contoh kantor: "214/09/2026" (urut/bulan/tahun).
 */
class DeliveryReceipt extends Model
{
    protected $fillable = [
        'project_id', 'number', 'delivery_date', 'recipient_client_id',
        'recipient_up', 'documents', 'note', 'created_by_user_id',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'documents'     => 'array',
    ];

    /** Dokumen yang bisa dikirim => [label, satuan]. Jenis selalu "Asli". */
    public const DOCUMENT_TYPES = [
        'laporan'      => ['Laporan Hasil Penilaian Properti', 'Buku'],
        'invoice'      => ['Invoice', 'Lembar'],
        'kwitansi'     => ['Kwitansi', 'Lembar'],
        'faktur_pajak' => ['Faktur Pajak', 'Lembar'],
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function recipient()
    {
        return $this->belongsTo(Client::class, 'recipient_client_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** "214 - Tanda Terima_PT Kuarta Powerindo Perkasa" (nama file unduhan). */
    public function fileName(): string
    {
        $seq    = explode('/', $this->number)[0];
        $client = $this->recipient?->client_name ?: $this->project->effective_client_name;
        $client = trim(preg_replace('/\s+/', ' ', preg_replace('#[\\/:*?"<>|]+#', ' ', (string) $client)));

        return $seq . ' - Tanda Terima_' . ($client ?: 'Klien');
    }

    /** Baris dokumen siap cetak: [no, label, qty, satuan, jenis]. */
    public function documentRows(): array
    {
        $rows = [];
        foreach ((array) $this->documents as $key => $row) {
            // Format baru: [['key' => 'laporan', 'qty' => 2], ...].
            $docKey = is_array($row) ? ($row['key'] ?? null) : $key;
            $qty    = (int) (is_array($row) ? ($row['qty'] ?? 0) : $row);

            if (! isset(self::DOCUMENT_TYPES[$docKey]) || $qty < 1) {
                continue;
            }
            [$label, $unit] = self::DOCUMENT_TYPES[$docKey];
            $rows[] = ['label' => $label, 'qty' => $qty, 'unit' => $unit, 'kind' => 'Asli'];
        }

        return $rows;
    }
}
