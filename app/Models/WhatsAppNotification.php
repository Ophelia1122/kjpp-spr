<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan notifikasi WhatsApp satu tombol alur proyek (2026-09-22,
 * feedback user). Tombol yang belum pernah disimpan memakai defaults().
 */
class WhatsAppNotification extends Model
{
    protected $table = 'whatsapp_notifications';

    protected $fillable = ['step', 'enabled', 'recipients', 'group_jid', 'template'];

    protected $casts = [
        'enabled'    => 'boolean',
        'recipients' => 'array',
    ];

    /** Penanda yang bisa dipakai di isi pesan => keterangannya. */
    public const PLACEHOLDERS = [
        '{judul}'          => 'Nama langkah, mis. "Submit Review Nilai"',
        '{nomor_proposal}' => 'Nomor proposal',
        '{klien}'          => 'Nama klien',
        '{pemberi_tugas}'  => 'Nama Pemberi Tugas',
        '{oleh}'           => 'Nama pengguna yang menekan tombol',
        '{catatan}'        => 'Catatan / alasan (baris ini dihapus bila kosong)',
        '{tahap}'          => 'Tahap proyek setelah tombol ditekan',
        '{mention}'        => 'Mention orang-orang penerima',
        '{link}'           => 'Tautan ke halaman proyek',
    ];

    /**
     * Keterangan awam tiap tombol di halaman pengaturan (2026-09-22, feedback
     * user): apa yang terjadi + siapa yang menekan. Dua tombol "Ke Surveyor"
     * untuk draft dibedakan jelas (Admin Produksi vs Reviewer).
     */
    public const STEP_LABELS = [
        'submit_value'          => ['Surveyor mengajukan nilai untuk direview', 'Surveyor'],
        'release_resume'        => ['Reviewer merilis Draft Resume', 'Reviewer'],
        'approve_value'         => ['Draft Resume disetujui, SLA Laporan Final mulai', 'Reviewer / Admin Produksi'],
        'appeal_resume'         => ['Draft Resume dibanding (dicatat di riwayat)', 'Reviewer'],
        'return_value'          => ['Nilai dikembalikan ke Surveyor untuk direvisi', 'Reviewer / Admin Produksi'],
        'submit_draft'          => ['Surveyor selesai membuat draft laporan', 'Surveyor'],
        'confirm_draft'         => ['Admin Produksi meneruskan draft laporan ke Reviewer', 'Admin Produksi'],
        'return_draft_admin'    => ['Admin Produksi mengembalikan draft laporan ke Surveyor', 'Admin Produksi'],
        'review_draft'          => ['Reviewer selesai mereview draft laporan', 'Reviewer'],
        'return_draft_reviewer' => ['Reviewer mengembalikan draft laporan ke Surveyor', 'Reviewer'],
        'mark_printed'          => ['Buku laporan selesai dicetak, proyek Selesai', 'Admin Produksi'],
    ];

    /** Kelompok penerima => label. Ditambah 'jabatan:<nama>' dan 'user:<id>'. */
    public const RECIPIENT_GROUPS = [
        'reviewers'  => 'Reviewer di Surat Tugas (bila kosong: semua Reviewer aktif)',
        'appraisers' => 'Semua penilai lapangan proyek',
        'submitter'  => 'Pengguna yang mengajukan review nilai',
    ];

    private const T_SUBMIT = "📝 *{judul}*\n\nProyek *{nomor_proposal}*\nKlien: {klien}\nDari: {oleh}\nCatatan: {catatan}\n\n{mention} mohon direview 🙏\n{link}";
    private const T_RETURN = "↩️ *{judul}*\n\nProyek *{nomor_proposal}*\nKlien: {klien}\nDikembalikan oleh: {oleh}\nAlasan: {catatan}\n\n{mention} mohon direvisi 🙏\n{link}";
    private const T_INFO   = "🔔 *{judul}*\n\nProyek *{nomor_proposal}*\nKlien: {klien}\nOleh: {oleh}\nTahap: {tahap}\nCatatan: {catatan}\n\n{mention}\n{link}";

    /**
     * Bawaan per tombol. Yang aktif = tiga pemicu lama (Submit Review,
     * Konfirmasi Draft, Ke Surveyor) dengan pesan dan penerima yang sama.
     */
    public static function defaults(): array
    {
        $info = fn (array $to) => ['enabled' => false, 'recipients' => $to, 'template' => self::T_INFO];

        return [
            'submit_value'          => ['enabled' => true, 'recipients' => ['reviewers'], 'template' => str_replace('{judul}', 'Pengajuan Review Nilai', self::T_SUBMIT)],
            'release_resume'        => $info(['appraisers']),
            'approve_value'         => $info(['appraisers']),
            'appeal_resume'         => $info(['appraisers']),
            'return_value'          => ['enabled' => true, 'recipients' => ['submitter', 'appraisers'], 'template' => self::T_RETURN],
            'submit_draft'          => $info(['jabatan:' . User::JABATAN_ADMIN]),
            'confirm_draft'         => ['enabled' => true, 'recipients' => ['reviewers'], 'template' => str_replace('{judul}', 'Review Draft Laporan', self::T_SUBMIT)],
            'return_draft_admin'    => ['enabled' => true, 'recipients' => ['submitter', 'appraisers'], 'template' => self::T_RETURN],
            'review_draft'          => $info(['jabatan:' . User::JABATAN_ADMIN]),
            'return_draft_reviewer' => ['enabled' => true, 'recipients' => ['submitter', 'appraisers'], 'template' => self::T_RETURN],
            'mark_printed'          => $info(['appraisers']),
        ];
    }

    /** Pengaturan tersimpan, atau bawaan bila belum pernah disimpan. */
    public static function forStep(string $step): self
    {
        $saved = static::where('step', $step)->first();
        if ($saved) {
            return $saved;
        }

        return new static(['step' => $step] + (self::defaults()[$step] ?? ['enabled' => false, 'recipients' => [], 'template' => self::T_INFO]));
    }

    /** Semua tombol alur, urut sesuai Project::WORKFLOW_STEPS. */
    public static function allSteps(): \Illuminate\Support\Collection
    {
        $saved = static::all()->keyBy('step');

        return collect(array_keys(Project::WORKFLOW_STEPS))->mapWithKeys(fn ($step) => [
            $step => $saved[$step] ?? new static(['step' => $step] + (self::defaults()[$step] ?? ['enabled' => false, 'recipients' => [], 'template' => self::T_INFO])),
        ]);
    }
}
