<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Bot notifikasi WhatsApp (2026-09-14, feedback user) — mengirim pesan ke grup
 * WhatsApp kantor lewat Evolution API (container sendiri di NAS) dan me-mention
 * pengguna terkait. Pemicu saat ini:
 *   - Surveyor mengajukan review   -> mention Reviewer
 *   - Reviewer mengembalikan proyek -> mention Surveyor yang mengajukan
 *
 * Pengaturan disimpan di app_settings (halaman Pengaturan Sistem > Bot WhatsApp),
 * dengan cadangan dari .env. Kegagalan kirim TIDAK pernah menggagalkan aksi
 * pengguna — hanya dicatat ke log.
 */
class WhatsAppNotifier
{
    public const KEYS = ['enabled', 'base_url', 'api_key', 'instance', 'group_jid'];

    public static function setting(string $key): ?string
    {
        $env = [
            'enabled'   => env('WA_BOT_ENABLED'),
            'base_url'  => env('WA_BOT_URL'),
            'api_key'   => env('WA_BOT_API_KEY'),
            'instance'  => env('WA_BOT_INSTANCE', 'kjpp'),
            'group_jid' => env('WA_BOT_GROUP_JID'),
        ][$key] ?? null;

        return AppSetting::getValue('wa_' . $key, $env !== null ? (string) $env : null);
    }

    public static function isConfigured(): bool
    {
        return filter_var(self::setting('enabled'), FILTER_VALIDATE_BOOLEAN)
            && self::setting('base_url') && self::setting('api_key')
            && self::setting('instance') && self::setting('group_jid');
    }

    /** "0812-3456 7890" / "+62 812..." -> "6281234567890"; null bila kosong. */
    public static function normalizeNumber(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        return $digits;
    }

    // ===================================================================
    // Pemicu
    // ===================================================================

    public static function reviewSubmitted(Project $project, ?User $submitter, ?string $note = null, string $title = 'Pengajuan Review Nilai'): void
    {
        $project->loadMissing('assignmentStaff.user');

        // Reviewer yang ditugaskan di Surat Tugas proyek ini; bila tidak ada,
        // semua pengguna aktif berjabatan Reviewer.
        $reviewers = $project->assignmentStaff->pluck('user')->filter()
            ->filter(fn (User $u) => $u->isReviewer() && $u->is_active)
            ->unique('id')->values();
        if ($reviewers->isEmpty()) {
            $reviewers = User::where('jabatan', User::JABATAN_REVIEWER)->where('is_active', true)->get();
        }

        $lines = [
            '📝 *' . $title . '*',
            '',
            'Proyek *' . $project->proposal_number . '*',
            'Klien: ' . ($project->effective_client_name ?? '-'),
            'Dari: ' . ($submitter->name ?? '-'),
            ...(filled($note) ? ['Catatan: ' . $note] : []),
            '',
            self::mentionLine($reviewers) . ' mohon direview 🙏',
            route('proposals.show', $project),
        ];

        self::sendToGroup(implode("\n", $lines), $reviewers);
    }

    public static function reviewReturned(Project $project, ?User $reviewer, string $reason, string $title = 'Dikembalikan ke Surveyor'): void
    {
        // Mention pengaju review & SEMUA penilai lapangan proyek (bisa orang yang sama).
        $project->loadMissing('reviewSubmittedBy', 'appraisers');
        $surveyors = collect([$project->reviewSubmittedBy])
            ->merge($project->appraisers->isNotEmpty() ? $project->appraisers : [User::find($project->assigned_appraiser_id)])
            ->filter()->unique('id')->values();

        $lines = [
            '↩️ *' . $title . '*',
            '',
            'Proyek *' . $project->proposal_number . '*',
            'Klien: ' . ($project->effective_client_name ?? '-'),
            'Dikembalikan oleh: ' . ($reviewer->name ?? '-'),
            'Alasan: ' . $reason,
            '',
            self::mentionLine($surveyors) . ' mohon direvisi 🙏',
            route('proposals.show', $project),
        ];

        self::sendToGroup(implode("\n", $lines), $surveyors);
    }

    // ===================================================================
    // Evolution API
    // ===================================================================

    /** "@6281... @6285..." — pengguna tanpa nomor WA ditulis namanya saja. */
    private static function mentionLine($users): string
    {
        $parts = collect($users)->map(function (User $u) {
            $n = self::normalizeNumber($u->whatsapp_number);
            return $n ? '@' . $n : $u->name;
        });

        return $parts->isEmpty() ? 'Tim' : $parts->implode(' ');
    }

    public static function sendToGroup(string $text, $mentionUsers = []): bool
    {
        if (! self::isConfigured()) {
            return false;
        }

        $mentioned = collect($mentionUsers)
            ->map(fn (User $u) => self::normalizeNumber($u->whatsapp_number))
            ->filter()->unique()->values()->all();

        return self::send(self::setting('group_jid'), $text, $mentioned);
    }

    /** Kirim teks ke nomor/grup. Mengembalikan true bila API menerima pesan. */
    public static function send(string $to, string $text, array $mentioned = []): bool
    {
        try {
            $response = self::client()->post('/message/sendText/' . rawurlencode(self::setting('instance')), array_filter([
                'number'    => $to,
                'text'      => $text,
                'mentioned' => $mentioned ?: null,
            ]));

            if ($response->failed()) {
                Log::warning('WhatsApp bot: gagal kirim', ['status' => $response->status(), 'body' => $response->body()]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp bot: ' . $e->getMessage());
            return false;
        }
    }

    /** Daftar grup yang diikuti nomor bot: [['id' => '...@g.us', 'subject' => '...'], ...]. */
    public static function fetchGroups(): array
    {
        $response = self::client()->timeout(20)
            ->get('/group/fetchAllGroups/' . rawurlencode(self::setting('instance')), ['getParticipants' => 'false']);
        $response->throw();

        return collect($response->json())
            ->map(fn ($g) => ['id' => $g['id'] ?? '', 'subject' => $g['subject'] ?? '(tanpa nama)'])
            ->filter(fn ($g) => str_ends_with($g['id'], '@g.us'))
            ->sortBy('subject')->values()->all();
    }

    private static function client()
    {
        return Http::baseUrl(rtrim((string) self::setting('base_url'), '/'))
            ->withHeaders(['apikey' => (string) self::setting('api_key')])
            ->acceptJson()
            ->timeout(10);
    }
}
