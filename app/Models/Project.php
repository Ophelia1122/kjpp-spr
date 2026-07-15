<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    /**
     * Konstanta status workflow. Dipakai di controller supaya tidak ada
     * string status yang "hardcode" tersebar di banyak file.
     */
    public const STATUS_DRAFT             = 'Draft Proposal';
    public const STATUS_WAITING_APPROVAL  = 'Menunggu Persetujuan Klien';
    public const STATUS_DP_INVOICING      = 'DP Invoicing';
    public const STATUS_IN_PROGRESS       = 'In-Progress / Scheduled';
    public const STATUS_PELUNASAN         = 'Pelunasan';
    public const STATUS_SELESAI           = 'Selesai';

    /**
     * Konstanta jenis/tujuan proposal. Menentukan teks Dasar Nilai,
     * Maksud Penilaian, dan klausul kondisional mana yang dipakai
     * di pdf/proposal.blade.php.
     */
    public const PURPOSE_JUAL_BELI         = 'Jual Beli';
    public const PURPOSE_PENJAMINAN_UTANG  = 'Penjaminan Utang';
    public const PURPOSE_LELANG            = 'Lelang';
    public const PURPOSE_LK_PROPERTI       = 'Pelaporan Keuangan';

    /**
     * SLA (hari kerja) berdasarkan report_style.
     * Sesuai spesifikasi: Terinci = 7 hari kerja, Ringkas = 3 hari kerja.
     */
    public const SLA_MAP = [
        'Terinci' => 7,
        'Ringkas' => 3,
    ];

    protected $fillable = [
        'proposal_number',
        'instructing_client_id',
        'property_owner_name',
        'asset_type',
        'asset_address',
        'service_fee',
        'report_style',
        'proposal_purpose',
        'psak_classification',
        'financial_reporting_date',
        'is_public_company',
        'assigned_appraiser',
        'survey_date',
        'final_report_number',
        'status',
    ];

    protected $casts = [
        'service_fee'              => 'decimal:2',
        'survey_date'               => 'date',
        'financial_reporting_date'  => 'date',
        'is_public_company'         => 'boolean',
    ];

    public function instructingClient()
    {
        return $this->belongsTo(Client::class, 'instructing_client_id');
    }

    /**
     * Pengguna Laporan bisa lebih dari 1 instansi per proyek.
     */
    public function intendedUsers()
    {
        return $this->belongsToMany(
            Client::class,
            'project_intended_users',
            'project_id',
            'client_id'
        )->withTimestamps();
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function dpInvoice()
    {
        return $this->hasOne(Invoice::class)->where('invoice_type', 'DP');
    }

    public function finalInvoice()
    {
        return $this->hasOne(Invoice::class)->where('invoice_type', 'Pelunasan');
    }

    /**
     * Accessor: jumlah hari SLA otomatis mengikuti report_style.
     * Dipakai di tampilan Proposal & PDF, tidak perlu disimpan manual di DB.
     */
    public function getSlaDaysAttribute(): int
    {
        return self::SLA_MAP[$this->report_style] ?? 7;
    }

    /**
     * Estimasi tanggal selesai kerja = survey_date + SLA hari kerja.
     * Memakai Carbon::addWeekdays() (butuh nesbot/carbon-diff-in-weekdays
     * yang sudah include di Carbon 2.x lewat method bawaan) sehingga
     * Sabtu & Minggu otomatis di-skip tanpa loop manual.
     * Mengembalikan Carbon instance null jika survey_date belum diisi.
     */
    public function getEstimatedCompletionDateAttribute(): ?\Carbon\Carbon
    {
        if (!$this->survey_date) {
            return null;
        }

        return $this->survey_date->copy()->addWeekdays($this->sla_days);
    }

    /**
     * Versi terformat untuk ditampilkan langsung di Blade/PDF.
     */
    public function getEstimatedCompletionDateFormattedAttribute(): ?string
    {
        return $this->estimated_completion_date?->translatedFormat('d F Y');
    }

    /**
     * Mapping status -> kelas warna badge Tailwind. Dipusatkan di sini
     * supaya konsisten dipakai di halaman manapun (show, index, dsb),
     * tidak ditulis ulang di setiap Blade.
     */
    public function getStatusBadgeClassesAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT            => 'bg-gray-100 text-gray-700 border border-gray-300',
            self::STATUS_WAITING_APPROVAL => 'bg-blue-100 text-blue-700 border border-blue-300',
            self::STATUS_DP_INVOICING     => 'bg-yellow-100 text-yellow-800 border border-yellow-300',
            self::STATUS_IN_PROGRESS      => 'bg-green-100 text-green-700 border border-green-300',
            self::STATUS_PELUNASAN        => 'bg-orange-100 text-orange-700 border border-orange-300',
            self::STATUS_SELESAI          => 'bg-emerald-100 text-emerald-800 border border-emerald-300',
            default                       => 'bg-gray-100 text-gray-700 border border-gray-300',
        };
    }

    /**
     * =========================================================================
     * LOGIKA PER JENIS PROPOSAL
     * Semua keputusan "teks mana yang tampil di PDF" dipusatkan di sini
     * (bukan di Blade) supaya Blade cukup memanggil accessor, tidak perlu
     * tahu detail aturan bisnis tiap jenis proposal.
     * =========================================================================
     */

    /**
     * Nilai Likuidasi WAJIB muncul untuk Lelang, OPSIONAL untuk Penjaminan
     * Utang (bank bisa minta atau tidak — di dokumen asli ditulis "pada
     * beberapa kasus"), dan tidak relevan untuk Jual Beli / LK Properti.
     */
    public function getRequiresLiquidationValueAttribute(): bool
    {
        return in_array($this->proposal_purpose, [
            self::PURPOSE_LELANG,
            self::PURPOSE_PENJAMINAN_UTANG,
        ]);
    }

    /**
     * Nilai Likuidasi tampil sebagai WAJIB (bukan sekadar opsi/interpretasi)
     * hanya di Lelang. Dipakai Blade untuk membedakan kalimat "akan
     * disajikan" (Lelang) vs "dapat diminta oleh bank" (Penjaminan Utang).
     */
    public function getLiquidationValueIsMandatoryAttribute(): bool
    {
        return $this->proposal_purpose === self::PURPOSE_LELANG;
    }

    public function getRequiresExposureTimeAttribute(): bool
    {
        return $this->proposal_purpose === self::PURPOSE_LELANG;
    }

    /**
     * LK Properti pakai Nilai Wajar, tiga jenis lain pakai Nilai Pasar.
     */
    public function getPrimaryValueBasisAttribute(): string
    {
        return $this->proposal_purpose === self::PURPOSE_LK_PROPERTI
            ? 'Nilai Wajar'
            : 'Nilai Pasar';
    }

    /**
     * Label lengkap Dasar Nilai untuk ditampilkan di PDF, mis:
     * "Nilai Pasar" / "Nilai Pasar dan Nilai Likuidasi" / "Nilai Wajar".
     */
    public function getValueBasisLabelAttribute(): string
    {
        if ($this->requires_liquidation_value && $this->liquidation_value_is_mandatory) {
            return 'Nilai Pasar dan Nilai Likuidasi';
        }

        return $this->primary_value_basis;
    }

    /**
     * Klausul POJK 28/POJK.04/2021 hanya relevan untuk LK Properti DAN
     * klien berstatus perusahaan terbuka (sesuai komentar c15 di dokumen
     * asli: "Jika perusahaan tertutup maka keterangan POJK 28 dihapus").
     */
    public function getShowsPojk28ClauseAttribute(): bool
    {
        return $this->proposal_purpose === self::PURPOSE_LK_PROPERTI
            && $this->is_public_company;
    }

    /**
     * Tanggal penilaian yang dipakai di PDF berbeda per jenis proposal:
     * LK Properti pakai financial_reporting_date (cut-off laporan
     * keuangan), jenis lain pakai survey_date (tanggal inspeksi terakhir).
     */
    public function getValuationDateAttribute(): ?\Carbon\Carbon
    {
        return $this->proposal_purpose === self::PURPOSE_LK_PROPERTI
            ? $this->financial_reporting_date
            : $this->survey_date;
    }

    /**
     * Halaman tanda tangan Lelang punya baris tambahan "Mengetahui,
     * [Bank]" di bawah tanda tangan KJPP — Bank yang dimaksud adalah
     * instructing_client karena di dokumen Lelang, Pemberi Tugas SELALU
     * pihak bank.
     */
    public function getShowsBankAcknowledgementAttribute(): bool
    {
        return $this->proposal_purpose === self::PURPOSE_LELANG;
    }
}
