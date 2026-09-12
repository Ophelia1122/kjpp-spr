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
    public const STATUS_BATAL             = 'Batal';

    /**
     * Label PENDEK status untuk badge di tabel daftar proyek (2026-09-14,
     * feedback user — "In-Progress / Scheduled" dan "Menunggu Persetujuan
     * Klien" membuat kolom status jadi terlalu lebar).
     *
     * Sengaja hanya label tampilan: nilai yang TERSIMPAN di database tetap
     * versi panjang, jadi tidak perlu migrasi data dan filter status, export
     * Excel, serta seluruh pengecekan status di controller tetap jalan.
     */
    public const STATUS_SHORT_LABELS = [
        self::STATUS_DRAFT            => 'Draft',
        self::STATUS_WAITING_APPROVAL => 'Menunggu Klien',
        self::STATUS_DP_INVOICING     => 'Invoice DP',
        self::STATUS_IN_PROGRESS      => 'In-Progress',
        self::STATUS_PELUNASAN        => 'Pelunasan',
        self::STATUS_SELESAI          => 'Selesai',
        self::STATUS_BATAL            => 'Batal',
    ];

    public function getStatusShortAttribute(): string
    {
        return self::STATUS_SHORT_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Urutan kanonik status untuk dropdown filter & widget dashboard.
     * "Batal" ditaruh paling akhir karena bukan bagian dari alur normal.
     */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_WAITING_APPROVAL,
        self::STATUS_DP_INVOICING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_PELUNASAN,
        self::STATUS_SELESAI,
        self::STATUS_BATAL,
    ];

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
     * Jenis laporan penilaian.
     * Long Report  = Laporan Terinci (Comprehensive Style Report)
     * Short Report = Laporan Ringkas (Short Form Report)
     * SLA tidak lagi hardcode — diinput manual per proyek lewat
     * sla_draft_days & sla_final_days (lihat migration 2024_01_08_000002).
     */
    public const REPORT_LONG  = 'Long Report';
    public const REPORT_SHORT = 'Short Report';

    /**
     * Tahap alur review (Surveyor -> Reviewer -> Admin Produksi) yang
     * menentukan kapan SLA Laporan Final mulai dihitung. Terpisah dari
     * status proyek utama & dari alur Invoice Pelunasan — lihat migration
     * 2024_01_13_000001_add_review_workflow_to_projects_table.
     *   null       = belum diajukan (giliran Surveyor submit)
     *   SUBMITTED  = menunggu Reviewer (giliran Reviewer)
     *   REVIEWED   = menunggu konfirmasi Admin Produksi
     *   APPROVED   = dikonfirmasi -> review_approved_at = mulai SLA Final
     */
    public const REVIEW_SUBMITTED = 'submitted';
    public const REVIEW_REVIEWED  = 'reviewed';
    public const REVIEW_APPROVED  = 'approved';

    /**
     * Skema pembayaran (2026-09-14, feedback user): sebagian klien baru
     * bayar di tengah/akhir pengerjaan, tanpa DP di muka. Dipilih SEKALI
     * saat proposal masih Draft/Menunggu Persetujuan (lihat
     * ProposalController) — PAYMENT_SCHEME_LATER membuka tombol "Mulai
     * Pekerjaan (Tanpa DP)" di kartu Aksi Tersedia (lihat
     * ProjectController::startWorkWithoutDp()). Invoicing sesudahnya
     * tetap fleksibel seperti biasa, tidak ada perubahan di situ.
     */
    public const PAYMENT_SCHEME_DP    = 'DP di Awal';
    public const PAYMENT_SCHEME_LATER = 'Bayar Nanti';

    public const PAYMENT_SCHEMES = [
        self::PAYMENT_SCHEME_DP,
        self::PAYMENT_SCHEME_LATER,
    ];

    protected $fillable = [
        'proposal_number',
        'proposal_date',
        'request_basis',
        'instructing_client_id',
        'asset_type',
        'asset_address',
        'service_fee',
        'fee_ppn_included',
        'fee_breakdown',
        'transport_cost',
        'report_style',
        'sla_draft_days',
        'sla_final_days',
        'proposal_purpose',
        'payment_scheme',
        'psak_classification',
        'financial_reporting_date',
        'is_public_company',
        'assigned_appraiser',
        'assigned_appraiser_id',
        'signed_by_user_id',
        'approver_name',
        'bank_id',
        'tax_invoice_number',
        'tax_invoice_date',
        'assignment_letter_number',
        'assignment_letter_date',
        'assignment_letter_barcode',
        'survey_date',
        'final_report_number',
        'final_report_date',
        'final_report_notes',
        'status',
        'status_before_cancel',
        'cancelled_at',
        'review_status',
        'review_submitted_at',
        'review_submitted_by_user_id',
        'reviewed_at',
        'reviewed_by_user_id',
        'review_approved_at',
        'review_approved_by_user_id',
        'review_rejected_at',
        'review_rejected_by_user_id',
        'review_rejection_note',
    ];

    protected $casts = [
        'service_fee'              => 'decimal:2',
        'transport_cost'           => 'decimal:2',
        'fee_ppn_included'         => 'boolean',
        'fee_breakdown'            => 'boolean',
        'sla_draft_days'           => 'integer',
        'sla_final_days'           => 'integer',
        'proposal_date'             => 'date',
        'survey_date'               => 'date',
        'financial_reporting_date'  => 'date',
        'tax_invoice_date'          => 'date',
        'final_report_date'         => 'date',
        'assignment_letter_date'    => 'date',
        'cancelled_at'              => 'datetime',
        'is_public_company'         => 'boolean',
        'review_submitted_at'       => 'datetime',
        'reviewed_at'               => 'datetime',
        'review_approved_at'        => 'datetime',
        'review_rejected_at'        => 'datetime',
    ];

    /** Proyek sedang dalam status Batal (data tetap ada, hanya dinonaktifkan). */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_BATAL;
    }

    /**
     * Skema "Bayar Nanti" — boleh mulai kerja lapangan tanpa invoice/DP
     * lebih dulu lewat ProjectController::startWorkWithoutDp().
     */
    public function isPaymentDeferred(): bool
    {
        return $this->payment_scheme === self::PAYMENT_SCHEME_LATER;
    }

    /**
     * Proyek "aktif" = masih berjalan: belum Selesai dan tidak Batal.
     * Dipakai Beranda & Timeline supaya definisinya satu pintu.
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_SELESAI, self::STATUS_BATAL]);
    }

    /**
     * Sisa hari kalender menuju $deadline. Positif = masih ada sisa, 0 =
     * jatuh tempo hari ini, negatif = lewat deadline. Null kalau $deadline
     * kosong. Helper bersama untuk SLA Draft & SLA Final (rumus sama,
     * tanggal acuan beda) supaya tidak dobel-tulis.
     */
    private function daysRemainingUntil(?\Carbon\Carbon $deadline): ?int
    {
        if (! $deadline) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($deadline->copy()->startOfDay(), false);
    }

    /**
     * Ringkasan kondisi SLA untuk pewarnaan badge/bar:
     *   none     = belum terjadwal
     *   done     = $done true (biasanya proyek sudah Selesai)
     *   overdue  = lewat deadline
     *   due-soon = tersisa 0-2 hari
     *   on-track = masih longgar
     */
    private function slaStateFor(?int $daysLeft, bool $done): string
    {
        if ($done) {
            return 'done';
        }

        return match (true) {
            $daysLeft === null => 'none',
            $daysLeft < 0      => 'overdue',
            $daysLeft <= 2     => 'due-soon',
            default            => 'on-track',
        };
    }

    /** Keterangan singkat sisa SLA, mis. "sisa 3 hari" / "lewat 2 hari". */
    private function slaLabelFor(?int $daysLeft, bool $done): string
    {
        return match (true) {
            $done              => 'Selesai',
            $daysLeft === null => 'Belum terjadwal',
            $daysLeft < 0      => 'lewat ' . abs($daysLeft) . ' hari',
            $daysLeft === 0    => 'deadline hari ini',
            default            => 'sisa ' . $daysLeft . ' hari',
        };
    }

    /**
     * Sisa hari menuju deadline Draf Laporan (estimated_completion_date).
     * Null kalau survey_date / sla_draft_days belum diisi (belum terjadwal).
     */
    public function getSlaDaysRemainingAttribute(): ?int
    {
        return $this->daysRemainingUntil($this->estimated_completion_date);
    }

    public function getSlaStateAttribute(): string
    {
        return $this->slaStateFor($this->sla_days_remaining, $this->status === self::STATUS_SELESAI);
    }

    public function getSlaLabelAttribute(): string
    {
        return $this->slaLabelFor($this->sla_days_remaining, $this->status === self::STATUS_SELESAI);
    }

    /**
     * Tanggal proposal untuk kop dokumen ("Jakarta, <tanggal>").
     * Fallback ke tanggal pembuatan record bila belum diisi (data lama).
     */
    public function getEffectiveProposalDateAttribute(): \Carbon\Carbon
    {
        return $this->proposal_date ?? $this->created_at;
    }

    /**
     * =========================================================================
     * BIAYA JASA PENILAIAN
     *
     * `service_fee` = FEE jasa profesional (nilai dasar yang diinput).
     * PPN dikenakan HANYA atas Fee — Transport & Akomodasi adalah
     * penggantian biaya (reimbursement), tidak dikenai PPN.
     *
     *  - fee_ppn_included = true  : `service_fee` sudah termasuk PPN. Fee net
     *    = service_fee / (1+rate); PPN = selisihnya; total tidak digross-up.
     *  - fee_ppn_included = false : PPN ditambahkan di atas `service_fee`.
     *
     * total_fee (gross, dipakai proposal .docx + invoice/kwitansi/dashboard):
     *   included : service_fee + transport
     *   excluded : service_fee + (service_fee × rate) + transport
     * =========================================================================
     */
    public function getFeePpnRateAttribute(): float
    {
        return (float) config('kjpp.ppn_rate', 0.11);
    }

    /** Nilai PPN (Rupiah) — hanya atas Fee jasa profesional. */
    public function getFeePpnAmountAttribute(): float
    {
        $base = (float) $this->service_fee;
        $rate = $this->fee_ppn_rate;

        return $this->fee_ppn_included
            ? round($base - $base / (1 + $rate), 2)   // PPN yang sudah di dalam Fee
            : round($base * $rate, 2);                 // PPN ditambahkan di atas Fee
    }

    /** Komponen "Fee" (jasa profesional) NET pada tabel rincian. */
    public function getFeeProfessionalAttribute(): float
    {
        $base = (float) $this->service_fee;

        return $this->fee_ppn_included
            ? round($base / (1 + $this->fee_ppn_rate), 2)
            : round($base, 2);
    }

    /** Nilai Transport & Akomodasi (sesuai input, tanpa PPN). */
    public function getFeeTransportDisplayAttribute(): float
    {
        return round((float) ($this->transport_cost ?? 0), 2);
    }

    /** Subtotal net = Fee net + Transport (sebelum PPN ditambahkan). */
    public function getFeeNetSubtotalAttribute(): float
    {
        return round($this->fee_professional + $this->fee_transport_display, 2);
    }

    /** Total biaya final (gross) — angka besar di proposal & dasar penagihan. */
    public function getTotalFeeAttribute(): float
    {
        $base      = (float) $this->service_fee;
        $transport = (float) ($this->transport_cost ?? 0);

        return $this->fee_ppn_included
            ? round($base + $transport, 2)
            : round($base + $base * $this->fee_ppn_rate + $transport, 2);
    }

    public function instructingClient()
    {
        return $this->belongsTo(Client::class, 'instructing_client_id');
    }

    /**
     * User (akun sistem) yang ditugaskan sebagai penilai lapangan.
     * Dipakai untuk filter "Proyek Saya" di dashboard — TIDAK dipakai
     * di PDF (PDF tetap pakai kolom assigned_appraiser yang berupa teks,
     * supaya nama tetap tercetak walau akunnya suatu saat dihapus).
     */
    public function assignedAppraiser()
    {
        return $this->belongsTo(User::class, 'assigned_appraiser_id');
    }

    /**
     * User (jabatan "Penanggung Jawab") yang menandatangani proposal.
     * Biodata-nya (nama + nomor izin MAPPI/RMK/Menkeu/OJK/Klasifikasi)
     * mengisi blok tanda tangan .docx/PDF. Null = pakai penandatangan
     * baku dari config('kjpp.signatory').
     */
    public function signedBy()
    {
        return $this->belongsTo(User::class, 'signed_by_user_id');
    }

    /**
     * Daftar petugas yang dicetak di tabel "Adapun petugas kami" pada
     * Surat Tugas — jumlah & komposisi jabatan bebas per proyek (mis. 2
     * Penilai + 1 Reviewer, atau 1 Reviewer + 1 Penilai + 1 Pelaksana
     * Inspeksi). Nama/Jabatan/No. MAPPI yang tercetak diambil dari
     * biodata user masing-masing (lihat ProjectAssignmentStaff), bukan
     * dari field terpisah di sini. Diurutkan sesuai urutan ditambahkan.
     */
    public function assignmentStaff()
    {
        return $this->hasMany(ProjectAssignmentStaff::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Empat akun yang berperan pada alur review SLA Final (lihat konstanta
     * REVIEW_* & accessor review_* di bawah). Semuanya belongsTo(User) —
     * dipisah per tahap supaya jejak "siapa & kapan" jelas per aksi.
     */
    public function reviewSubmittedBy()
    {
        return $this->belongsTo(User::class, 'review_submitted_by_user_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function reviewApprovedBy()
    {
        return $this->belongsTo(User::class, 'review_approved_by_user_id');
    }

    public function reviewRejectedBy()
    {
        return $this->belongsTo(User::class, 'review_rejected_by_user_id');
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
    
    public function valuationObjects()
    {
        return $this->hasMany(ProjectValuationObject::class)->orderBy('sort_order');
    }

    /**
     * Override teks baku proposal per-bab (Batch 3 — editor teks per-bab).
     * Hanya bab yang diedit staf yang punya baris; sisanya pakai teks baku
     * config/proposal_clauses.php. Dikonsumsi App\Services\ProposalDocxBuilder.
     */
    public function sectionTexts()
    {
        return $this->hasMany(ProposalSectionText::class);
    }

    /**
     * Rekening bank yang dipilih di proposal (Batch 4 / Feature 5). Null =
     * pakai bank ber-is_default (fallback: config('kjpp.bank_account')).
     * Dipakai blok "Rekening Bank" proposal .docx + PDF Invoice.
     */
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    /** Rekening efektif untuk dokumen: pilihan proposal -> default -> null. */
    public function effectiveBank(): ?Bank
    {
        return $this->bank ?? Bank::default();
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * =========================================================================
     * TAGIHAN & PEMBAYARAN — model FLEKSIBEL: proyek boleh punya berapa
     * pun invoice/termin (bukan cuma "DP" + "Pelunasan"). Sistem TIDAK
     * peduli itu termin ke berapa — cukup jumlahkan yang sudah Paid vs
     * total_fee utk tahu sisa tagihannya berapa.
     * =========================================================================
     */

    /** Total yang SUDAH dibayar (invoice berstatus Paid). */
    public function getTotalPaidAttribute(): float
    {
        return round((float) $this->invoices->where('status', Invoice::STATUS_PAID)->sum('amount'), 2);
    }

    /** Total yang SUDAH diterbitkan tapi belum dibayar. */
    public function getTotalUnpaidInvoicedAttribute(): float
    {
        return round((float) $this->invoices->where('status', Invoice::STATUS_UNPAID)->sum('amount'), 2);
    }

    /** Sisa tagihan = total_fee - yang sudah Paid. Tidak pernah negatif. */
    public function getRemainingBalanceAttribute(): float
    {
        return max(0, round((float) $this->total_fee - $this->total_paid, 2));
    }

    /** Lunas penuh kalau yang sudah Paid >= total_fee (toleransi Rp 1 pembulatan). */
    public function getIsFullyPaidAttribute(): bool
    {
        return $this->total_paid >= ((float) $this->total_fee - 1);
    }

    /**
     * Label lengkap jenis laporan untuk ditampilkan di Blade/PDF, mis:
     * "Long Report (Laporan Terinci / Comprehensive Style Report)".
     */
    public function getReportStyleLabelAttribute(): string
    {
        return match ($this->report_style) {
            self::REPORT_LONG  => 'Long Report (Laporan Terinci / Comprehensive Style Report)',
            self::REPORT_SHORT => 'Short Report (Laporan Ringkas / Short Form Report)',
            default            => (string) $this->report_style,
        };
    }

    /**
     * SLA "utama" yang dipakai untuk hitung mundur di halaman detail
     * proyek = jangka waktu Laporan Draft/Resume (hari kerja sejak
     * inspeksi terakhir). Null kalau belum diisi.
     */
    public function getSlaDaysAttribute(): ?int
    {
        return $this->sla_draft_days;
    }

    /**
     * Estimasi tanggal Draft/Resume Laporan selesai = survey_date +
     * sla_draft_days hari kerja. Memakai Carbon::addWeekdays() sehingga
     * Sabtu & Minggu otomatis di-skip. Null kalau survey_date atau
     * sla_draft_days belum diisi.
     */
    public function getEstimatedCompletionDateAttribute(): ?\Carbon\Carbon
    {
        if (!$this->survey_date || !$this->sla_draft_days) {
            return null;
        }

        return $this->survey_date->copy()->addWeekdays($this->sla_draft_days);
    }

    /**
     * Versi terformat untuk ditampilkan langsung di Blade/PDF.
     */
    public function getEstimatedCompletionDateFormattedAttribute(): ?string
    {
        return $this->estimated_completion_date?->translatedFormat('d F Y');
    }

    /**
     * =========================================================================
     * ALUR REVIEW SLA FINAL (Surveyor -> Reviewer -> Admin Produksi)
     *
     * SLA Draft/Resume (di atas) mulai dihitung sejak survey_date. SLA
     * Laporan Final BARU mulai dihitung setelah nilai "disetujui" lewat
     * 3 tahap berikut (independen dari status proyek & invoice pelunasan):
     *   1. Surveyor  : "Submit untuk Review"   -> review_status = submitted
     *   2. Reviewer  : "Tandai Sudah Direview" -> review_status = reviewed
     *                  (atau kembalikan ke Surveyor -> review_status = null)
     *   3. Admin Produksi : "Konfirmasi Disetujui" -> review_status = approved,
     *      review_approved_at diisi -> titik mulai SLA Final.
     *      (atau kembalikan ke Reviewer -> review_status = submitted)
     * =========================================================================
     */

    /** Proyek sudah pernah diajukan review (submitted/reviewed/approved). */
    public function isReviewSubmitted(): bool
    {
        return $this->review_status !== null;
    }

    /** Menunggu keputusan Reviewer. */
    public function isAwaitingReviewer(): bool
    {
        return $this->review_status === self::REVIEW_SUBMITTED;
    }

    /** Sudah disetujui Reviewer, menunggu konfirmasi Admin Produksi. */
    public function isAwaitingProductionConfirmation(): bool
    {
        return $this->review_status === self::REVIEW_REVIEWED;
    }

    /** Sudah dikonfirmasi Admin Produksi -> SLA Final berjalan. */
    public function isReviewApproved(): bool
    {
        return $this->review_status === self::REVIEW_APPROVED;
    }

    /**
     * Estimasi tanggal Laporan Final selesai = review_approved_at +
     * sla_final_days hari kerja. Null kalau belum dikonfirmasi Admin
     * Produksi / sla_final_days belum diisi.
     */
    public function getEstimatedFinalCompletionDateAttribute(): ?\Carbon\Carbon
    {
        if (! $this->review_approved_at || ! $this->sla_final_days) {
            return null;
        }

        return $this->review_approved_at->copy()->addWeekdays($this->sla_final_days);
    }

    public function getEstimatedFinalCompletionDateFormattedAttribute(): ?string
    {
        return $this->estimated_final_completion_date?->translatedFormat('d F Y');
    }

    /** Sisa hari menuju deadline Laporan Final. Null kalau belum berjalan. */
    public function getFinalSlaDaysRemainingAttribute(): ?int
    {
        return $this->daysRemainingUntil($this->estimated_final_completion_date);
    }

    public function getFinalSlaStateAttribute(): string
    {
        return $this->slaStateFor($this->final_sla_days_remaining, $this->status === self::STATUS_SELESAI);
    }

    public function getFinalSlaLabelAttribute(): string
    {
        return $this->slaLabelFor($this->final_sla_days_remaining, $this->status === self::STATUS_SELESAI);
    }

    /**
     * Mapping status -> kelas warna badge Tailwind. Dipusatkan di sini
     * supaya konsisten dipakai di halaman manapun (show, index, dsb),
     * tidak ditulis ulang di setiap Blade.
     */
    public function getStatusBadgeClassesAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT            => 'bg-gray-100 text-gray-700 border border-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600',
            self::STATUS_WAITING_APPROVAL => 'bg-blue-100 text-blue-700 border border-blue-300 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800',
            self::STATUS_DP_INVOICING     => 'bg-yellow-100 text-yellow-800 border border-yellow-300 dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-800',
            self::STATUS_IN_PROGRESS      => 'bg-green-100 text-green-700 border border-green-300 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800',
            self::STATUS_PELUNASAN        => 'bg-orange-100 text-orange-700 border border-orange-300 dark:bg-orange-900/30 dark:text-orange-400 dark:border-orange-800',
            self::STATUS_SELESAI          => 'bg-emerald-100 text-emerald-800 border border-emerald-300 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800',
            self::STATUS_BATAL           => 'bg-rose-100 text-rose-700 border border-rose-300 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800',
            default                       => 'bg-gray-100 text-gray-700 border border-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600',
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

    /**
     * Ringkasan kategori objek untuk ditampilkan di dashboard/tabel (bukan PDF).
     * Contoh: "Tanah dan Bangunan" (kalau cuma 1 objek) atau
     * "3 Objek Penilaian" (kalau lebih dari 1, supaya kolom tabel tidak
     * kepanjangan menampilkan semua kategori sekaligus).
     */
    public function getAssetSummaryLabelAttribute(): string
    {
        $count = $this->valuationObjects->count();

        if ($count === 0) {
            return $this->asset_type ?? '-'; // fallback untuk data lama sebelum migrasi ini
        }

        if ($count === 1) {
            return $this->valuationObjects->first()->short_label;
        }

        return "{$count} Objek Penilaian";
    }
}
