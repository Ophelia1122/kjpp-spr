<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'note',
    ];

    // Log tidak pernah di-update setelah tercatat, jadi kolom
    // 'updated_at' sengaja tidak ada di migration & tidak dipakai di sini.
    const UPDATED_AT = null;

    /**
     * Label & warna titik untuk Riwayat Proyek di halaman detail proyek
     * (2026-09-14, feedback user). Aksi yang tidak terdaftar tetap tampil
     * dengan deskripsinya.
     */
    public const TIMELINE = [
        'proposal.created'                            => ['Proposal dibuat', 'blue'],
        'proposal.sent_to_client'                     => ['Proposal dikirim ke klien', 'blue'],
        'proposal.updated'                            => ['Proposal diubah', 'gray'],
        'proposal.cancelled'                          => ['Proyek dibatalkan', 'rose'],
        'proposal.reactivated'                        => ['Proyek diaktifkan kembali', 'blue'],
        'proposal.tax_invoice_set'                    => ['Faktur pajak diisi', 'gray'],
        'invoice.generated'                           => ['Invoice diterbitkan', 'blue'],
        'invoice.updated'                             => ['Invoice diubah', 'gray'],
        'invoice.paid'                                => ['Pembayaran diterima', 'emerald'],
        'invoice.cancelled'                           => ['Invoice dihapus', 'rose'],
        'project.status_reverted'                     => ['Status dikembalikan', 'amber'],
        'project.started_without_dp'                  => ['Pekerjaan dimulai tanpa DP', 'blue'],
        'survey.input'                                => ['Penilai & tanggal survei diisi', 'gray'],
        'project.assignment_letter_set'               => ['Surat Tugas diperbarui', 'gray'],
        'project.assignment_letter_barcode_uploaded'  => ['Barcode Surat Tugas diunggah', 'gray'],
        'project.assignment_letter_barcode_deleted'   => ['Barcode Surat Tugas dihapus', 'gray'],
        'project.assignment_staff_added'              => ['Petugas ditambahkan', 'gray'],
        'project.assignment_staff_removed'            => ['Petugas dihapus', 'gray'],
        'review.submitted'                            => ['Surveyor submit review nilai', 'blue'],
        'project.book_signed'                         => ['Buku ditandatangani', 'emerald'],
        'project.receipt_created'                     => ['Tanda Terima pengiriman dibuat', 'emerald'],
        'project.receipt_deleted'                     => ['Tanda Terima pengiriman dihapus', 'rose'],
        'review.resume_released'                      => ['Draft Resume dirilis', 'blue'],
        'review.value_approved'                       => ['Draft Resume disetujui — SLA Laporan Final berjalan', 'emerald'],
        'review.resume_appealed'                      => ['Draft Resume banding', 'amber'],
        'draft.submitted'                             => ['Surveyor: draft laporan sudah dibuat', 'blue'],
        'draft.confirmed'                             => ['Admin Produksi mengonfirmasi draft laporan', 'blue'],
        'draft.returned_by_admin'                     => ['Draft laporan dikembalikan ke Surveyor', 'rose'],
        'draft.reviewed'                              => ['Draft laporan telah direview', 'emerald'],
        'draft.returned_by_reviewer'                  => ['Reviewer mengembalikan draft laporan ke Surveyor', 'rose'],
        'project.book_printed'                        => ['Buku laporan selesai dicetak — proyek Selesai', 'emerald'],
        'review.approved_by_reviewer'                 => ['Reviewer selesai review', 'emerald'],
        'review.rejected_to_surveyor'                 => ['Nilai dikembalikan ke Surveyor', 'rose'],
        'review.confirmed'                            => ['Admin Produksi mengonfirmasi hasil review', 'emerald'],
        'review.rejected_to_reviewer'                 => ['Admin Produksi mengembalikan ke Reviewer', 'rose'],
        'project.draft_completed'                     => ['Draf laporan selesai', 'emerald'],
        'project.final_report_number_set'             => ['Nomor Laporan Final diisi', 'emerald'],
    ];

    /**
     * Warna titik riwayat memakai bahasa warna yang sama dengan badge status
     * (2026-09-25, feedback user): abu-abu = catatan administratif, biru =
     * diteruskan ke pihak lain, kuning = perlu tindakan/banding, merah =
     * dikembalikan/dihapus, hijau = disetujui/selesai. Legendanya tampil di
     * atas daftar riwayat.
     */
    public const TIMELINE_DOTS = [
        'gray'    => 'bg-gray-400 dark:bg-gray-500',
        'blue'    => 'bg-blue-500',
        'amber'   => 'bg-amber-500',
        'emerald' => 'bg-emerald-500',
        'rose'    => 'bg-rose-500',
    ];

    /** Keterangan warna untuk legenda di bawah judul Riwayat Proyek. */
    public const TIMELINE_LEGEND = [
        'gray'    => 'Catatan',
        'blue'    => 'Diteruskan',
        'amber'   => 'Perlu tindakan',
        'emerald' => 'Selesai/disetujui',
        'rose'    => 'Dikembalikan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Menunjuk ke row spesifik yang kena aksi (Project, Invoice, Client,
     * dst) — bisa null kalau aksinya tidak terkait 1 row data (mis. login).
     */
    public function subject()
    {
        return $this->morphTo();
    }

    public function getTimelineLabelAttribute(): string
    {
        return self::TIMELINE[$this->action][0] ?? $this->action;
    }

    public function getTimelineDotAttribute(): string
    {
        return self::TIMELINE_DOTS[self::TIMELINE[$this->action][1] ?? 'gray'];
    }
}
