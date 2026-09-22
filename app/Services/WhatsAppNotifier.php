<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Project;
use App\Models\User;
use App\Models\WhatsAppNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Bot notifikasi WhatsApp (2026-09-14, feedback user) — mengirim pesan ke grup
 * WhatsApp kantor lewat Evolution API (container sendiri di NAS) dan me-mention
 * pengguna terkait. Pemicu = tombol alur proyek; isi pesan, penerima, dan
 * grup tiap tombol diatur di Pengaturan Sistem > Bot WhatsApp (2026-09-22).
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
    // Pemicu: satu tombol alur proyek (2026-09-22)
    // ===================================================================

    /**
     * Kirim notifikasi untuk tombol alur $step sesuai pengaturan di
     * Pengaturan Sistem > Bot WhatsApp (isi pesan, penerima, grup).
     * $project sudah berisi tahap SETELAH tombol ditekan.
     */
    public static function workflowStep(Project $project, string $step, ?User $actor, ?string $note = null): bool
    {
        $cfg = WhatsAppNotification::forStep($step);
        if (! $cfg->enabled) {
            return false;
        }

        $users = self::recipients($project, (array) $cfg->recipients);
        $text  = self::render($cfg->template, $project, $step, $actor, $note, $users);

        return self::sendToGroup($text, $users, $cfg->group_jid);
    }

    /** Pengguna penerima mention dari daftar kelompok penerima. */
    public static function recipients(Project $project, array $groups)
    {
        $project->loadMissing('assignmentStaff.user', 'reviewSubmittedBy', 'appraisers');
        $users = collect();

        foreach ($groups as $group) {
            $users = $users->merge(match (true) {
                $group === 'reviewers'  => self::reviewersOf($project),
                $group === 'appraisers' => $project->appraisers->isNotEmpty()
                    ? $project->appraisers
                    : collect([User::find($project->assigned_appraiser_id)]),
                $group === 'submitter'  => collect([$project->reviewSubmittedBy]),
                str_starts_with($group, 'jabatan:') => User::where('jabatan', substr($group, 8))->where('is_active', true)->get(),
                str_starts_with($group, 'user:')    => User::whereKey((int) substr($group, 5))->where('is_active', true)->get(),
                default => collect(),
            });
        }

        return $users->filter()->unique('id')->values();
    }

    /** Reviewer di Surat Tugas proyek; bila tidak ada, semua Reviewer aktif. */
    private static function reviewersOf(Project $project)
    {
        $reviewers = $project->assignmentStaff->pluck('user')->filter()
            ->filter(fn (User $u) => $u->isReviewer() && $u->is_active)
            ->unique('id')->values();

        return $reviewers->isNotEmpty()
            ? $reviewers
            : User::where('jabatan', User::JABATAN_REVIEWER)->where('is_active', true)->get();
    }

    /** Isi pesan dengan penanda diganti. Baris {catatan} dihapus bila catatan kosong. */
    public static function render(string $template, Project $project, string $step, ?User $actor, ?string $note, $users): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $template);
        if (blank($note)) {
            $lines = array_filter($lines, fn ($l) => ! str_contains($l, '{catatan}'));
        }

        return trim(strtr(implode("\n", $lines), [
            '{judul}'          => Project::WORKFLOW_STEPS[$step]['title'] ?? $step,
            '{nomor_proposal}' => $project->proposal_number,
            '{klien}'          => $project->effective_client_name ?: '-',
            '{pemberi_tugas}'  => $project->instructingClient?->client_name ?? '-',
            '{oleh}'           => $actor?->name ?? '-',
            '{catatan}'        => (string) $note,
            '{tahap}'          => $project->stage['label'] ?? '-',
            '{mention}'        => self::mentionLine($users),
            '{link}'           => route('proposals.show', $project),
        ]));
    }

    // ===================================================================
    // Evolution API
    // ===================================================================

    /** "@6281... @6285..." — pengguna tanpa nomor WA ditulis namanya saja. */
    public static function mentionLine($users): string
    {
        $parts = collect($users)->map(function (User $u) {
            $n = self::normalizeNumber($u->whatsapp_number);
            return $n ? '@' . $n : $u->name;
        });

        return $parts->isEmpty() ? 'Tim' : $parts->implode(' ');
    }

    public static function sendToGroup(string $text, $mentionUsers = [], ?string $groupJid = null): bool
    {
        if (! self::isConfigured()) {
            return false;
        }

        $mentioned = collect($mentionUsers)
            ->map(fn (User $u) => self::normalizeNumber($u->whatsapp_number))
            ->filter()->unique()->values()->all();

        // Grup khusus per tombol; kosong = grup utama.
        return self::send($groupJid ?: self::setting('group_jid'), $text, $mentioned);
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

        $groups = collect($response->json())
            ->map(fn ($g) => ['id' => $g['id'] ?? '', 'subject' => $g['subject'] ?? '(tanpa nama)'])
            ->filter(fn ($g) => str_ends_with($g['id'], '@g.us'))
            ->sortBy('subject')->values()->all();

        // Disimpan supaya pilihan grup per tombol tampil dengan nama grup.
        AppSetting::putValue('wa_groups_cache', json_encode($groups));

        return $groups;
    }

    private static function client()
    {
        return Http::baseUrl(rtrim((string) self::setting('base_url'), '/'))
            ->withHeaders(['apikey' => (string) self::setting('api_key')])
            ->acceptJson()
            ->timeout(10);
    }
}
