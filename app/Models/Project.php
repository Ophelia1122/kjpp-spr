<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Project extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    /**
     * Konstanta status workflow. Dipakai di controller supaya tidak ada
     * string status yang "hardcode" tersebar di banyak file.
     */
    public const STATUS_DRAFT             = 'Draft Proposal';
    public const STATUS_WAITING_APPROVAL  = 'Menunggu Persetujuan Klien';
    public const STATUS_DP_INVOICING      = 'DP Invoicing';
    public const STATUS_IN_PROGRESS       = 'In-Progress / Scheduled';
    // Tahap lanjutan (2026-09-23, feedback user): Finalisasi = Draft Resume
    // disetujui s/d buku dicetak; lalu tanda tangan buku; lalu pengiriman buku
    // (dibuktikan Tanda Terima). Selesai - Belum Lunas dipakai bila buku sudah
    // dikirim tetapi pelunasan belum masuk (skema Bayar Nanti).
    public const STATUS_FINALISASI        = 'Finalisasi';
    public const STATUS_TANDA_TANGAN      = 'Tanda Tangan';
    public const STATUS_PENGIRIMAN        = 'Pengiriman Buku';
    public const STATUS_SELESAI_BELUM_LUNAS = 'Selesai - Belum Lunas';
    public const STATUS_SELESAI           = 'Selesai';
    public const STATUS_BATAL             = 'Batal';

    /** Status yang berarti "pekerjaan sedang berjalan" (alur produksi aktif). */
    public const WORK_STATUSES = [
        self::STATUS_IN_PROGRESS,
        self::STATUS_FINALISASI,
        self::STATUS_TANDA_TANGAN,
        self::STATUS_PENGIRIMAN,
    ];

    /** Status yang berarti pekerjaan sudah selesai (lunas maupun belum). */
    public const DONE_STATUSES = [
        self::STATUS_SELESAI_BELUM_LUNAS,
        self::STATUS_SELESAI,
    ];

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
        self::STATUS_FINALISASI       => 'Finalisasi',
        self::STATUS_TANDA_TANGAN     => 'Tanda Tangan',
        self::STATUS_PENGIRIMAN       => 'Pengiriman',
        self::STATUS_SELESAI_BELUM_LUNAS => 'Selesai, Belum Lunas',
        self::STATUS_SELESAI          => 'Selesai',
        self::STATUS_BATAL            => 'Batal',
    ];

    public function getStatusShortAttribute(): string
    {
        return self::STATUS_SHORT_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Nomor proposal versi ringkas untuk tabel: 5 karakter awal + "…" +
     * 2 segmen terakhir (bulan/tahun), mis.
     * "0023/2.0031-06/KJPPSPR-PRO/APR/06/2026" -> "0023/…/06/2026".
     * Nomor pendek (mis. "PRO/KJPP/2026/001") ditampilkan utuh.
     */
    /**
     * Ringkasan objek penilaian untuk List Project (2026-09-13, feedback
     * user): kategori pertama + jumlah kategori lain, mis. "Tanah +2".
     * 'full' berisi daftar lengkap untuk tooltip.
     */
    public function getObjectSummaryAttribute(): array
    {
        $labels = $this->valuationObjects->map(fn ($o) => $o->short_label)->filter()->unique()->values();

        if ($labels->isEmpty()) {
            return ['short' => $this->asset_type ?: '—', 'full' => (string) $this->asset_type];
        }

        return [
            'short' => $labels->first() . ($labels->count() > 1 ? ' +' . ($labels->count() - 1) : ''),
            'full'  => $labels->implode(', '),
        ];
    }

    public function getProposalNumberShortAttribute(): string
    {
        $number   = (string) $this->proposal_number;
        $segments = explode('/', $number);

        if (mb_strlen($number) <= 20 || count($segments) < 4) {
            return $number;
        }

        return mb_substr($number, 0, 5) . '…/' . implode('/', array_slice($segments, -2));
    }

    /**
     * Rincian lokasi di invoice untuk proyek dengan objek LEBIH DARI 5
     * (2026-09-14, feedback user; sebelumnya 8) — daftar lokasi diganti satu
     * kalimat rujukan ke proposal. Null = cukup sedikit, tulis daftarnya.
     * Dipakai PDF (pdf/invoice) dan Word (InvoiceDocxBuilder).
     */
    public function getInvoiceLocationSummaryAttribute(): ?string
    {
        $count = $this->valuationObjects->count();

        if ($count <= 5) {
            return null;
        }

        return 'Berdasarkan rincian lokasi pada Nomor Proposal ' . $this->proposal_number
            . ' tanggal ' . $this->effective_proposal_date->translatedFormat('d F Y')
            . ' sebanyak ' . $count . ' lokasi';
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
        self::STATUS_FINALISASI,
        self::STATUS_TANDA_TANGAN,
        self::STATUS_PENGIRIMAN,
        self::STATUS_SELESAI_BELUM_LUNAS,
        self::STATUS_SELESAI,
        self::STATUS_BATAL,
    ];

    /**
     * Konstanta jenis/tujuan proposal. Menentukan teks Dasar Nilai,
     * Maksud Penilaian, dan klausul kondisional mana yang dipakai
     * di ProposalDocxBuilder.
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
     * Tahap alur produksi laporan, disimpan di kolom review_status
     * (2026-09-15, feedback user). null = Surveyor masih survei & menilai.
     */
    public const REVIEW_SUBMITTED      = 'submitted';        // menunggu review nilai
    public const REVIEW_RELEASED       = 'resume_released';  // Draft Resume dirilis, menunggu disetujui / banding (2026-09-21)
    public const REVIEW_APPROVED       = 'approved';         // nilai disetujui (SLA Final mulai), Surveyor menyusun draft
    public const STAGE_DRAFT_SUBMITTED = 'draft_submitted';  // menunggu konfirmasi Admin Produksi
    public const STAGE_DRAFT_CONFIRMED = 'draft_confirmed';  // menunggu Reviewer me-review draft laporan
    public const STAGE_DRAFT_REVIEWED  = 'draft_reviewed';   // proses cetak buku (Admin Produksi)
    public const STAGE_PRINTED         = 'printed';          // buku dicetak -> proses tanda tangan
    public const STAGE_SIGNED          = 'signed';           // buku ditandatangani -> proses pengiriman
    public const STAGE_DELIVERED       = 'delivered';        // buku dikirim (ada Tanda Terima)

    /**
     * Tombol alur produksi. actor: surveyor (izin survey.manage), reviewer
     * (jabatan Reviewer/Administrator), admin (izin proposals.manage),
     * reviewer_or_admin (keduanya). note: optional = catatan boleh kosong,
     * required = alasan pengembalian wajib. Dipakai ProjectController::
     * advanceWorkflow() & floating action bar.
     */
    public const WORKFLOW_STEPS = [
        'submit_value' => [
            'from' => null, 'to' => self::REVIEW_SUBMITTED, 'actor' => 'surveyor', 'note' => 'optional',
            'title' => 'Submit Review Nilai', 'button' => 'Submit Review', 'tip' => 'Ajukan nilai hasil penilaian ke Reviewer',
            'action' => 'review.submitted', 'desc' => 'Mengajukan nilai hasil penilaian untuk direview',
            'flash' => 'Nilai diajukan untuk direview.',
        ],
        // Draft Resume (2026-09-21, feedback user): Reviewer merilis Draft
        // Resume, lalu mencatat hasilnya — disetujui (SLA Final berjalan)
        // atau banding. Banding hanya dicatat di riwayat (catatan wajib);
        // tahap tetap "Draft Resume dirilis" sampai akhirnya disetujui.
        // Release & banding hanya Reviewer (dan Administrator); Admin Produksi
        // hanya boleh menekan "Resume Disetujui".
        'release_resume' => [
            'from' => self::REVIEW_SUBMITTED, 'to' => self::REVIEW_RELEASED, 'actor' => 'reviewer', 'note' => 'optional',
            'title' => 'Release Draft Resume', 'button' => 'Release Resume', 'tip' => 'Rilis Draft Resume hasil review nilai',
            'action' => 'review.resume_released', 'desc' => 'Merilis Draft Resume hasil review nilai',
            'flash' => 'Draft Resume dirilis. Catat hasilnya: disetujui atau banding.',
        ],
        'approve_value' => [
            'from' => self::REVIEW_RELEASED, 'to' => self::REVIEW_APPROVED, 'actor' => 'reviewer_or_admin', 'note' => 'optional',
            'title' => 'Draft Resume Disetujui', 'button' => 'Resume Disetujui', 'tip' => 'Draft Resume disetujui — SLA Laporan Final berjalan',
            'hint' => 'SLA Laporan Final mulai dihitung sejak Draft Resume disetujui.',
            'action' => 'review.value_approved', 'desc' => 'Draft Resume disetujui. SLA Laporan Final mulai dihitung',
            'flash' => 'Draft Resume disetujui. SLA Laporan Final berjalan.',
        ],
        'appeal_resume' => [
            'from' => self::REVIEW_RELEASED, 'to' => self::REVIEW_RELEASED, 'actor' => 'reviewer_or_admin', 'note' => 'required',
            'stay' => true,
            'title' => 'Draft Resume Banding', 'button' => 'Resume Banding', 'tip' => 'Catat banding atas Draft Resume beserta catatannya',
            'action' => 'review.resume_appealed', 'desc' => 'Mencatat banding atas Draft Resume',
            'flash' => 'Banding Draft Resume dicatat di riwayat proyek.',
        ],
        'return_value' => [
            'from' => self::REVIEW_SUBMITTED, 'to' => null, 'actor' => 'reviewer', 'note' => 'required',
            'title' => 'Kembalikan Nilai ke Surveyor', 'button' => 'Ke Surveyor', 'tip' => 'Kembalikan nilai ke Surveyor untuk revisi',
            'action' => 'review.rejected_to_surveyor', 'desc' => 'Mengembalikan nilai ke Surveyor untuk revisi',
            'flash' => 'Nilai dikembalikan ke Surveyor untuk revisi.',
        ],
        'submit_draft' => [
            'from' => self::REVIEW_APPROVED, 'to' => self::STAGE_DRAFT_SUBMITTED, 'actor' => 'surveyor', 'note' => 'optional',
            'title' => 'Draft Narasi Sudah Dibuat', 'button' => 'Draft Narasi Dibuat', 'tip' => 'Tandai draft narasi laporan sudah dibuat — lanjut ke Admin Produksi',
            'action' => 'draft.submitted', 'desc' => 'Menandai draft narasi laporan sudah dibuat',
            'flash' => 'Draft narasi laporan dikirim ke Admin Produksi.',
        ],
        'confirm_draft' => [
            'from' => self::STAGE_DRAFT_SUBMITTED, 'to' => self::STAGE_DRAFT_CONFIRMED, 'actor' => 'admin', 'note' => 'optional',
            'title' => 'Konfirmasi Draft Laporan', 'button' => 'Konfirmasi Draft', 'tip' => 'Konfirmasi draft laporan — kirim ke Reviewer',
            'action' => 'draft.confirmed', 'desc' => 'Mengonfirmasi draft laporan dan mengirimnya ke Reviewer',
            'flash' => 'Draft laporan dikonfirmasi dan dikirim ke Reviewer.',
        ],
        'return_draft_admin' => [
            'from' => self::STAGE_DRAFT_SUBMITTED, 'to' => self::REVIEW_APPROVED, 'actor' => 'admin', 'note' => 'required',
            'title' => 'Kembalikan Draft ke Surveyor', 'button' => 'Ke Surveyor', 'tip' => 'Kembalikan draft laporan ke Surveyor',
            'action' => 'draft.returned_by_admin', 'desc' => 'Mengembalikan draft laporan ke Surveyor',
            'flash' => 'Draft laporan dikembalikan ke Surveyor.',
        ],
        'review_draft' => [
            'from' => self::STAGE_DRAFT_CONFIRMED, 'to' => self::STAGE_DRAFT_REVIEWED, 'actor' => 'reviewer', 'note' => 'optional',
            'title' => 'Draft Laporan Telah Direview', 'button' => 'Telah Direview', 'tip' => 'Tandai draft laporan telah direview — lanjut proses cetak',
            'action' => 'draft.reviewed', 'desc' => 'Menandai draft laporan telah direview, lanjut proses cetak buku',
            'flash' => 'Draft laporan telah direview. Lanjut proses cetak buku.',
        ],
        'return_draft_reviewer' => [
            'from' => self::STAGE_DRAFT_CONFIRMED, 'to' => self::REVIEW_APPROVED, 'actor' => 'reviewer', 'note' => 'required',
            'title' => 'Kembalikan Draft ke Surveyor', 'button' => 'Ke Surveyor', 'tip' => 'Kembalikan draft laporan ke Surveyor untuk revisi',
            'action' => 'draft.returned_by_reviewer', 'desc' => 'Mengembalikan draft laporan ke Surveyor untuk revisi',
            'flash' => 'Draft laporan dikembalikan ke Surveyor.',
        ],
        'mark_printed' => [
            'from' => self::STAGE_DRAFT_REVIEWED, 'to' => self::STAGE_PRINTED, 'actor' => 'admin', 'note' => 'optional',
            'title' => 'Buku Selesai Dicetak', 'button' => 'Buku Dicetak', 'tip' => 'Konfirmasi buku laporan selesai dicetak — lanjut proses tanda tangan',
            'hint' => 'SLA Laporan Final dihentikan dan proyek masuk proses tanda tangan.',
            'action' => 'project.book_printed', 'desc' => 'Mengonfirmasi buku laporan selesai dicetak. SLA Laporan Final selesai, lanjut proses tanda tangan',
            'flash' => 'Buku laporan selesai dicetak. Lanjut proses tanda tangan.',
        ],
        // Tanda tangan & pengiriman buku (2026-09-23, feedback user).
        'mark_signed' => [
            'from' => self::STAGE_PRINTED, 'to' => self::STAGE_SIGNED, 'actor' => 'admin_or_keuangan', 'note' => 'optional',
            'title' => 'Buku Selesai Ditandatangani', 'button' => 'Buku Ditandatangani', 'tip' => 'Buku sudah ditandatangani — lanjut proses pengiriman',
            'action' => 'project.book_signed', 'desc' => 'Menandai buku laporan selesai ditandatangani, lanjut proses pengiriman',
            'flash' => 'Buku ditandatangani. Lanjut proses pengiriman buku.',
        ],
    ];

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
        'transport_reimbursed',
        'client_name',
        'client_id',
        'report_style',
        'sla_draft_days',
        'sla_final_days',
        'proposal_purpose',
        'payment_scheme',
        'payment_terms',
        'psak_classification',
        'financial_reporting_date',
        'is_public_company',
        'assigned_appraiser',
        'assigned_appraiser_id',
        'signed_by_user_id',
        'use_signature_barcode',
        'signature_barcode',
        'use_stamp',
        'representative_limited',
        'approver_name',
        'approver_client_id',
        'marketing_name',
        'bank_id',
        'tax_invoice_number',
        'tax_invoice_date',
        'assignment_letter_number',
        'assignment_letter_date',
        'assignment_letter_on_behalf_client_id',
        'assignment_letter_request_basis',
        'assignment_letter_recipient_client_id',
        'assignment_letter_barcode',
        'survey_date',
        'valuation_date_manual',
        'signed_at',
        'delivered_at',
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
        'draft_submitted_at',
        'draft_confirmed_at',
        'draft_reviewed_at',
        'printed_at',
    ];

    protected $casts = [
        'service_fee'              => 'decimal:2',
        'transport_cost'           => 'decimal:2',
        'fee_ppn_included'         => 'boolean',
        'fee_breakdown'            => 'boolean',
        'use_signature_barcode'    => 'boolean',
        'use_stamp'                => 'boolean',
        'representative_limited'   => 'boolean',
        'transport_reimbursed'     => 'boolean',
        'sla_draft_days'           => 'integer',
        'sla_final_days'           => 'integer',
        'proposal_date'             => 'date',
        'survey_date'               => 'date',
        'valuation_date_manual'     => 'date',
        'signed_at'                 => 'datetime',
        'delivered_at'              => 'datetime',
        'payment_terms'             => 'array',
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
        'draft_submitted_at'        => 'datetime',
        'draft_confirmed_at'        => 'datetime',
        'draft_reviewed_at'         => 'datetime',
        'printed_at'                => 'datetime',
    ];

    /** Proyek sedang dalam status Batal (data tetap ada, hanya dinonaktifkan). */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_BATAL;
    }

    /**
     * Tanggal survei terakhir dari seluruh objek (selesai, atau mulai bila
     * selesai kosong = survei 1 hari). Null bila objek belum punya tanggal.
     */
    public function lastSurveyDate(): ?\Carbon\Carbon
    {
        $dates = $this->valuationObjects
            ->map(fn ($o) => $o->survey_end_date ?: $o->survey_start_date)
            ->filter();

        return $dates->isEmpty() ? null : $dates->max();
    }

    /** Pekerjaan sedang berjalan (In-Progress s/d Pengiriman Buku). */
    public function isWorkActive(): bool
    {
        return in_array($this->status, self::WORK_STATUSES, true);
    }

    /** Pekerjaan sudah selesai — termasuk "Selesai - Belum Lunas". */
    public function isDone(): bool
    {
        return in_array($this->status, self::DONE_STATUSES, true);
    }

    /**
     * Termin pembayaran masih boleh diubah selama proyek belum masuk tahap
     * pencetakan buku (2026-09-24, feedback user). Sebelum itu nominal
     * tagihan masih bisa menyesuaikan kesepakatan dengan klien; setelah buku
     * dicetak, angkanya sudah dipakai di dokumen dan tidak boleh bergeser.
     * Administrator tetap bisa mengubah, sesuai aturan kunci lainnya.
     */
    public function canEditPaymentTerms(?User $user = null): bool
    {
        if ($user?->isAdministrator() && ! $this->isCancelled()) {
            return true;
        }

        $printingStages = [
            self::STAGE_DRAFT_REVIEWED,
            self::STAGE_PRINTED,
            self::STAGE_SIGNED,
            self::STAGE_DELIVERED,
        ];

        $printingStatuses = [
            self::STATUS_TANDA_TANGAN,
            self::STATUS_PENGIRIMAN,
            self::STATUS_SELESAI_BELUM_LUNAS,
            self::STATUS_SELESAI,
        ];

        return ! in_array($this->review_status, $printingStages, true)
            && ! in_array($this->status, $printingStatuses, true);
    }

    /**
     * Status akhir setelah buku dikirim: "Selesai" bila sudah lunas, selain
     * itu "Selesai - Belum Lunas" (dipanggil saat Tanda Terima dibuat dan
     * saat invoice ditandai lunas).
     */
    public function finalStatusAfterDelivery(): string
    {
        return $this->is_fully_paid ? self::STATUS_SELESAI : self::STATUS_SELESAI_BELUM_LUNAS;
    }

    /**
     * Skema "Bayar Nanti" — boleh mulai kerja lapangan tanpa invoice/DP
     * lebih dulu lewat ProjectController::startWorkWithoutDp().
     */
    /**
     * Persentase termin pembayaran (2026-09-19, feedback user).
     * Default ikut skema: DP di Awal = 50/50, Bayar Nanti = 100% di akhir.
     * Staf boleh menimpanya per proposal, misalnya 30/70.
     */
    public function paymentTermPercents(): array
    {
        $saved = array_values(array_filter((array) ($this->payment_terms ?? []), fn ($n) => (float) $n > 0));
        if ($saved) {
            return array_map(fn ($n) => (float) $n, $saved);
        }

        return $this->isPaymentDeferred() ? [100.0] : [50.0, 50.0];
    }

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
     * PPN dikenakan atas Fee DAN Transport & Akomodasi (TA) — 2026-09-15,
     * keputusan user, menyamakan dengan pembukuan kantor. Sebelumnya TA
     * dianggap tanpa PPN. Invoice/Kwitansi sejak awal sudah memecah PPN dari
     * seluruh nominal, jadi aturan ini membuat proposal konsisten dengannya.
     *
     *  - fee_ppn_included = true  : Fee & TA yang diinput SUDAH termasuk PPN.
     *    Nilai net = input / (1+rate); PPN = total - total / (1+rate).
     *  - fee_ppn_included = false : PPN ditambahkan di atas (Fee + TA).
     *  - transport_reimbursed     : TA ditanggung klien — tidak ikut ditagih,
     *    tidak masuk total, dan proposal diberi catatan.
     *
     * total_fee (gross, dipakai proposal .docx + invoice/kwitansi/dashboard):
     *   included : fee + TA
     *   excluded : (fee + TA) × (1 + rate)
     * =========================================================================
     */
    public function getFeePpnRateAttribute(): float
    {
        return (float) config('kjpp.ppn_rate', 0.11);
    }

    /** TA yang ikut DITAGIH (angka input). 0 bila ditanggung klien/reimburse. */
    public function getBillableTransportAttribute(): float
    {
        return $this->transport_reimbursed ? 0.0 : round((float) ($this->transport_cost ?? 0), 2);
    }

    /** Nilai PPN (Rupiah) — atas Fee + TA yang ditagih. */
    public function getFeePpnAmountAttribute(): float
    {
        $taxable = (float) $this->service_fee + $this->billable_transport;
        $rate    = $this->fee_ppn_rate;

        return $this->fee_ppn_included
            ? round($taxable - $taxable / (1 + $rate), 2)   // PPN yang sudah di dalam input
            : round($taxable * $rate, 2);                    // PPN ditambahkan di atas
    }

    /** Komponen "Fee" (jasa profesional) NET pada tabel rincian. */
    public function getFeeProfessionalAttribute(): float
    {
        $base = (float) $this->service_fee;

        return $this->fee_ppn_included
            ? round($base / (1 + $this->fee_ppn_rate), 2)
            : round($base, 2);
    }

    /** Komponen "Transport" NET pada tabel rincian (TA yang ditagih, tanpa PPN). */
    public function getFeeTransportDisplayAttribute(): float
    {
        $transport = $this->billable_transport;

        return $this->fee_ppn_included
            ? round($transport / (1 + $this->fee_ppn_rate), 2)
            : round($transport, 2);
    }

    /** Total biaya final (gross) — angka besar di proposal & dasar penagihan. */
    public function getTotalFeeAttribute(): float
    {
        $taxable = (float) $this->service_fee + $this->billable_transport;

        return $this->fee_ppn_included
            ? round($taxable, 2)
            : round($taxable * (1 + $this->fee_ppn_rate), 2);
    }

    /**
     * "Nama Klien" untuk ditampilkan/dicetak (baris "Hal" proposal): isian
     * manual kalau ada, kalau kosong jatuh ke nama Pemberi Tugas.
     */
    public function getEffectiveClientNameAttribute(): string
    {
        // Klien terpilih dari Database Klien (2026-09-14) -> teks lama -> Pemberi Tugas.
        return (string) (optional($this->namedClient)->client_name
            ?: trim((string) $this->client_name)
            ?: optional($this->instructingClient)->client_name);
    }

    /** Surat Tugas: "Kepada Yth" (2026-09-14, feedback user). */
    public function assignmentLetterRecipientClient()
    {
        return $this->belongsTo(Client::class, 'assignment_letter_recipient_client_id');
    }

    /** Penerima "Kepada Yth" di Surat Tugas — belum dipilih = Pemberi Tugas (data lama). */
    public function getAssignmentLetterRecipientAttribute(): ?Client
    {
        return $this->assignmentLetterRecipientClient ?? $this->instructingClient;
    }

    /** Surat Tugas: "Penilaian Aset atas nama …" (2026-09-14, feedback user). */
    public function assignmentLetterOnBehalfClient()
    {
        return $this->belongsTo(Client::class, 'assignment_letter_on_behalf_client_id');
    }

    /** Nama "atas nama" di Surat Tugas — belum dipilih = Pemberi Tugas (data lama). */
    public function getAssignmentLetterOnBehalfNameAttribute(): string
    {
        return (string) (optional($this->assignmentLetterOnBehalfClient)->client_name
            ?? optional($this->instructingClient)->client_name);
    }

    /** Dasar Permintaan di Surat Tugas — belum diisi = Dasar Permintaan di Identitas Proposal. */
    public function getAssignmentLetterRequestBasisTextAttribute(): string
    {
        return trim((string) ($this->assignment_letter_request_basis ?? $this->request_basis));
    }

    /** "Nama Klien" — dipilih dari Database Klien (2026-09-14, feedback user). */
    public function namedClient()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Pilihan "Telah diterima dari" di modal invoice (2026-09-14, feedback user):
     * Pemberi Tugas, Nama Klien, lalu Pengguna Laporan — klien yang sama
     * (mis. Pemberi Tugas sekaligus Pengguna Laporan) cukup muncul sekali.
     *
     * @return \Illuminate\Support\Collection<int, array{id:int, name:string, address:string, label:string}>
     */
    public function receivedFromOptions(): \Illuminate\Support\Collection
    {
        $map = [];
        $add = function ($client, string $role) use (&$map) {
            if (! $client) {
                return;
            }
            $map[$client->id] ??= ['client' => $client, 'roles' => []];
            $map[$client->id]['roles'][] = $role;
        };

        $add($this->instructingClient, 'Pemberi Tugas');
        $add($this->namedClient, 'Nama Klien');
        foreach ($this->intendedUsers as $user) {
            $add($user, 'Pengguna Laporan');
        }

        return collect(array_values($map))->map(fn ($row) => [
            'id'      => $row['client']->id,
            'name'    => $row['client']->client_name,
            'address' => (string) $row['client']->address,
            'label'   => $row['client']->client_name . ' — ' . implode(', ', array_unique($row['roles'])),
        ]);
    }

    public function instructingClient()
    {
        return $this->belongsTo(Client::class, 'instructing_client_id');
    }

    /** "Pihak yang Menyetujui" — dipilih dari Database Klien. */
    public function approverClient()
    {
        return $this->belongsTo(Client::class, 'approver_client_id');
    }

    /**
     * Nama di kolom "Menyetujui," blok tanda tangan: klien yang dipilih, lalu
     * isian teks lama (data sebelum field ini jadi pilihan klien), terakhir
     * jatuh ke nama Pemberi Tugas.
     */
    public function getEffectiveApproverNameAttribute(): string
    {
        return (string) (optional($this->approverClient)->client_name
            ?: trim((string) $this->approver_name)
            ?: optional($this->instructingClient)->client_name);
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

    /** Maksimal penilai lapangan per proyek (5 sejak 2026-09-21; dulu 3). */
    public const MAX_APPRAISERS = 5;

    /**
     * Semua penilai lapangan proyek (1-5 orang, tanggung jawab SETARA).
     * assigned_appraiser_id/assigned_appraiser hanyalah ringkasan (urutan
     * pertama & gabungan nama) — kepemilikan proyek memakai relasi ini.
     */
    public function appraisers()
    {
        return $this->belongsToMany(User::class, 'project_appraisers')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('project_appraisers.sort_order');
    }

    /** Proyek yang salah satu penilai lapangannya adalah $userId. */
    public function scopeForAppraiser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereHas('appraisers', fn ($a) => $a->where('users.id', $userId))
              ->orWhere('assigned_appraiser_id', $userId);
        });
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

    /** Tanda Terima Pengiriman Buku (2026-09-23, boleh lebih dari satu). */
    public function deliveryReceipts()
    {
        return $this->hasMany(DeliveryReceipt::class)->latest('delivery_date')->latest('id');
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

    /** Total yang SUDAH ditagihkan (semua invoice, lunas maupun belum). */
    public function getTotalInvoicedAttribute(): float
    {
        return round((float) $this->invoices->sum('amount'), 2);
    }

    /**
     * Sisa Tagihan = nilai kontrak yang BELUM dibuatkan invoice
     * (2026-09-21, feedback user). Batas nominal invoice baru.
     */
    public function getUninvoicedBalanceAttribute(): float
    {
        return max(0, round((float) $this->total_fee - $this->total_invoiced, 2));
    }

    /**
     * Sisa Pelunasan = total_fee - yang sudah Paid (belum dibayar).
     * Nama atribut lama dipertahankan karena dipakai dashboard & export.
     */
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

        // Mulai dihitung hari kerja BERIKUTNYA setelah survei terakhir
        // (H+1, 2026-09-23 feedback user).
        return $this->survey_date->copy()->addWeekdays(1 + $this->sla_draft_days);
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
     * ALUR PRODUKSI LAPORAN (2026-09-15, feedback user) — lihat WORKFLOW_STEPS
     * & ProjectController::advanceWorkflow().
     *
     * SLA Draft/Resume (di atas) dihitung sejak survey_date. SLA Laporan Final
     * dihitung sejak nilai disetujui (review_approved_at) sampai buku selesai
     * dicetak (printed_at, proyek Selesai). Pembayaran tidak memengaruhi alur.
     * =========================================================================
     */

    /** Proyek sudah pernah diajukan review (submitted/reviewed/approved). */
    public function isReviewSubmitted(): bool
    {
        return $this->review_status !== null;
    }

    /** Nilai sudah disetujui -> SLA Laporan Final berjalan (atau sudah selesai). */
    public function isReviewApproved(): bool
    {
        return in_array($this->review_status, [
            self::REVIEW_APPROVED, self::STAGE_DRAFT_SUBMITTED, self::STAGE_DRAFT_CONFIRMED,
            self::STAGE_DRAFT_REVIEWED, self::STAGE_PRINTED,
        ], true);
    }

    /**
     * Penilai lapangan, tanggal survei & Surat Tugas boleh disiapkan?
     * (2026-09-15, feedback user) Hanya selama pekerjaan berjalan (In-Progress):
     * DP di awal -> setelah invoice DP dibayar; Bayar Nanti -> setelah tombol
     * "Mulai Tanpa DP" ditekan. Draft/Menunggu Klien/Selesai/Batal: terkunci.
     */
    public function canPrepareFieldwork(?User $user = null): bool
    {
        // Masih boleh diubah sampai tahap Finalisasi (2026-09-23) — mis.
        // tanggal survei per objek dikoreksi setelah nilai disetujui.
        // Administrator boleh mengubah kapan saja selama proyek tidak batal
        // (2026-09-23, feedback user).
        $user ??= auth()->user();
        if ($user?->isAdministrator() && ! $this->isCancelled()) {
            return true;
        }

        return in_array($this->status, [self::STATUS_IN_PROGRESS, self::STATUS_FINALISASI], true);
    }

    /** Nomor Laporan Final boleh diisi mulai draft laporan telah direview. */
    public function isFinalReportStage(): bool
    {
        return in_array($this->review_status, [
                self::STAGE_DRAFT_REVIEWED, self::STAGE_PRINTED, self::STAGE_SIGNED, self::STAGE_DELIVERED,
            ], true)
            || $this->isDone();
    }

    /** Apakah $user boleh bertindak sebagai "actor" langkah alur produksi. */
    public static function userCanActAs(User $user, string $actor): bool
    {
        $reviewer = $user->canActAsReviewer();
        $admin    = $user->hasPermission('proposals.manage');

        return match ($actor) {
            'surveyor'          => $user->hasPermission('survey.manage'),
            // Tahap tanda tangan & kirim buku: Admin Produksi atau General Admin.
            'admin_or_keuangan' => $admin || $user->hasPermission('invoices.manage'),
            'reviewer'          => $reviewer,
            'admin'             => $admin,
            'reviewer_or_admin' => $reviewer || $admin,
            default             => false,
        };
    }

    /** Langkah alur produksi yang bisa ditekan $user saat ini (key => definisi). */
    public function availableWorkflowSteps(User $user): array
    {
        if (! $this->isWorkActive() || ! $this->assigned_appraiser || ! $this->survey_date) {
            return [];
        }

        return array_filter(
            self::WORKFLOW_STEPS,
            fn ($step) => $step['from'] === $this->review_status && self::userCanActAs($user, $step['actor'])
        );
    }

    /** Tahap pekerjaan saat ini + siapa yang memegang giliran (Beranda, detail proyek). */
    public function getStageAttribute(): array
    {
        $stage = fn (string $label, ?string $actor = null) => ['label' => $label, 'actor' => $actor];

        return match (true) {
            $this->status === self::STATUS_BATAL   => $stage('Dibatalkan'),
            $this->isDone()                        => $stage($this->status === self::STATUS_SELESAI ? 'Selesai' : 'Selesai, menunggu pelunasan'),
            $this->status === self::STATUS_DRAFT            => $stage('Draft proposal', 'Admin Produksi'),
            $this->status === self::STATUS_WAITING_APPROVAL => $stage('Menunggu persetujuan klien', 'General Admin'),
            $this->status === self::STATUS_DP_INVOICING => $stage('Menunggu pembayaran DP', 'General Admin'),
            ! $this->assigned_appraiser || ! $this->survey_date => $stage('Menunggu jadwal survei', 'Admin Produksi'),
            default => match ($this->review_status) {
                self::REVIEW_SUBMITTED      => $stage('Review nilai', 'Reviewer / Admin Produksi'),
                self::REVIEW_RELEASED       => $stage('Draft Resume dirilis — menunggu disetujui', 'Reviewer / Admin Produksi'),
                self::REVIEW_APPROVED       => $stage('Penyusunan draft laporan', 'Surveyor'),
                self::STAGE_DRAFT_SUBMITTED => $stage('Konfirmasi draft laporan', 'Admin Produksi'),
                self::STAGE_DRAFT_CONFIRMED => $stage('Review draft laporan', 'Reviewer'),
                self::STAGE_DRAFT_REVIEWED  => $stage('Proses cetak buku', 'Admin Produksi'),
                self::STAGE_PRINTED         => $stage('Proses tanda tangan buku', 'Admin Produksi / General Admin'),
                self::STAGE_SIGNED          => $stage('Proses pengiriman buku', 'Admin Produksi / General Admin'),
                default                     => $stage('Survei & penilaian', 'Surveyor'),
            },
        };
    }

    /** Sejak kapan proyek berada di tahap saat ini (untuk "menunggu X hari"). */
    public function getStageSinceAttribute(): ?\Carbon\Carbon
    {
        return match ($this->review_status) {
            self::REVIEW_SUBMITTED      => $this->review_submitted_at,
            self::REVIEW_RELEASED       => $this->reviewed_at,
            self::REVIEW_APPROVED       => $this->review_rejected_at ?? $this->review_approved_at,
            self::STAGE_DRAFT_SUBMITTED => $this->draft_submitted_at,
            self::STAGE_DRAFT_CONFIRMED => $this->draft_confirmed_at,
            self::STAGE_DRAFT_REVIEWED  => $this->draft_reviewed_at,
            self::STAGE_PRINTED         => $this->printed_at,
            self::STAGE_SIGNED          => $this->signed_at,
            self::STAGE_DELIVERED       => $this->delivered_at,
            default                     => $this->review_rejected_at,
        };
    }

    /** SLA yang sedang berlaku: Final setelah nilai disetujui, selain itu Draft. */
    public function getActiveSlaAttribute(): ?array
    {
        // SLA hanya berjalan selama pekerjaan berjalan (2026-09-15).
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            return null;
        }
        if ($this->isReviewApproved() && $this->estimated_final_completion_date) {
            return ['phase' => 'Final', 'text' => $this->final_sla_label, 'state' => $this->final_sla_state,
                    'date' => $this->estimated_final_completion_date];
        }
        if ($this->estimated_completion_date) {
            return ['phase' => 'Draft', 'text' => $this->sla_label, 'state' => $this->sla_state,
                    'date' => $this->estimated_completion_date];
        }

        return null;
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
            // Indigo, selaras dengan badge tahapan di Beranda (2026-09-19, feedback user).
            self::STATUS_IN_PROGRESS      => 'bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-800',
            self::STATUS_FINALISASI       => 'bg-sky-100 text-sky-700 border border-sky-300 dark:bg-sky-900/30 dark:text-sky-300 dark:border-sky-800',
            self::STATUS_TANDA_TANGAN     => 'bg-violet-100 text-violet-700 border border-violet-300 dark:bg-violet-900/30 dark:text-violet-300 dark:border-violet-800',
            self::STATUS_PENGIRIMAN       => 'bg-teal-100 text-teal-700 border border-teal-300 dark:bg-teal-900/30 dark:text-teal-300 dark:border-teal-800',
            self::STATUS_SELESAI_BELUM_LUNAS => 'bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800',
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
        if ($this->proposal_purpose === self::PURPOSE_LK_PROPERTI) {
            return $this->financial_reporting_date;
        }

        // Isian manual menang; kosong = tanggal survei terakhir (2026-09-23).
        return $this->valuation_date_manual ?: $this->survey_date;
    }

}
