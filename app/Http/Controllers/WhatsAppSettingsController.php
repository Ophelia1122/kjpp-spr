<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\AppSetting;
use App\Models\Project;
use App\Models\User;
use App\Models\WhatsAppNotification;
use App\Services\WhatsAppNotifier;
use Illuminate\Http\Request;

/** Pengaturan Sistem > Bot WhatsApp (2026-09-14, feedback user). */
class WhatsAppSettingsController extends Controller
{
    public function edit()
    {
        $settings = collect(WhatsAppNotifier::KEYS)->mapWithKeys(fn ($k) => [$k => WhatsAppNotifier::setting($k)]);

        return view('settings.whatsapp', [
            'settings'      => $settings,
            'configured'    => WhatsAppNotifier::isConfigured(),
            // Notifikasi per tombol alur (2026-09-22).
            'notifications' => WhatsAppNotification::allSteps(),
            'groups'        => collect(json_decode((string) AppSetting::getValue('wa_groups_cache'), true) ?: []),
            'users'         => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'jabatan', 'whatsapp_number']),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'base_url'  => 'nullable|url|max:255',
            'api_key'   => 'nullable|string|max:255',
            'instance'  => 'nullable|string|max:100',
            'group_jid' => ['nullable', 'string', 'max:100', 'regex:/@g\.us$/'],
        ], ['group_jid.regex' => 'ID grup harus berakhiran @g.us — pilih lewat tombol "Ambil daftar grup".']);

        AppSetting::putValue('wa_enabled', $request->boolean('enabled') ? '1' : '0');
        AppSetting::putValue('wa_base_url', $validated['base_url'] ?? null);
        AppSetting::putValue('wa_instance', $validated['instance'] ?? null);
        AppSetting::putValue('wa_group_jid', $validated['group_jid'] ?? null);
        // API key dikosongkan = tetap memakai yang tersimpan.
        if (! empty($validated['api_key'])) {
            AppSetting::putValue('wa_api_key', $validated['api_key']);
        }

        AuditLogger::record('settings.whatsapp_updated', 'Mengubah pengaturan Bot WhatsApp');

        return back()->with('success', 'Pengaturan Bot WhatsApp disimpan.');
    }

    public function groups()
    {
        try {
            return response()->json(['groups' => WhatsAppNotifier::fetchGroups()]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Gagal mengambil daftar grup: ' . $e->getMessage()], 422);
        }
    }

    public function test()
    {
        if (! WhatsAppNotifier::isConfigured()) {
            return back()->with('error', 'Lengkapi & aktifkan pengaturan dulu sebelum tes kirim.');
        }

        $user = auth()->user();
        $number = WhatsAppNotifier::normalizeNumber($user->whatsapp_number);
        $ok = WhatsAppNotifier::sendToGroup(
            "✅ Tes Bot WhatsApp KJPP SPR\n" . ($number ? "Halo @{$number}, bot sudah tersambung." : 'Bot sudah tersambung.'),
            [$user]
        );

        return back()->with($ok ? 'success' : 'error', $ok
            ? 'Pesan tes terkirim ke grup.'
            : 'Pesan tes gagal terkirim. Cek URL, API key, instance, dan status koneksi WhatsApp di dashboard Evolution API.');
    }

    /** Simpan pengaturan notifikasi semua tombol alur sekaligus. */
    public function updateNotifications(Request $request)
    {
        $steps = array_keys(Project::WORKFLOW_STEPS);
        $data  = $request->validate([
            'n'                  => 'required|array',
            'n.*.template'       => 'required|string|max:2000',
            'n.*.group_jid'      => ['nullable', 'string', 'max:100', 'regex:/@g\.us$/'],
            'n.*.recipients'     => 'nullable|array',
            'n.*.recipients.*'   => 'string|max:60',
            'n.*.jabatan'        => 'nullable|array',
            'n.*.jabatan.*'      => ['string', \Illuminate\Validation\Rule::in(User::JABATAN_OPTIONS)],
            'n.*.users'          => 'nullable|array',
            'n.*.users.*'        => 'integer|exists:users,id',
        ], [
            'n.*.template.required' => 'Isi pesan tidak boleh kosong.',
            'n.*.group_jid.regex'   => 'ID grup harus berakhiran @g.us.',
        ])['n'];

        foreach ($steps as $step) {
            if (! isset($data[$step])) {
                continue;
            }
            $row = $data[$step];
            $recipients = collect($row['recipients'] ?? [])
                ->filter(fn ($r) => array_key_exists($r, WhatsAppNotification::RECIPIENT_GROUPS))
                ->merge(collect($row['jabatan'] ?? [])->map(fn ($j) => 'jabatan:' . $j))
                ->merge(collect($row['users'] ?? [])->map(fn ($id) => 'user:' . (int) $id))
                ->unique()->values()->all();

            WhatsAppNotification::updateOrCreate(['step' => $step], [
                'enabled'    => $request->boolean("n.$step.enabled"),
                'recipients' => $recipients,
                'group_jid'  => $row['group_jid'] ?? null,
                'template'   => $row['template'],
            ]);
        }

        AuditLogger::record('settings.whatsapp_updated', 'Mengubah pengaturan notifikasi Bot WhatsApp');

        return redirect()->route('settings.whatsapp.edit')->withFragment('notifikasi')
            ->with('success', 'Pengaturan notifikasi disimpan.');
    }

    /**
     * Kirim tes satu tombol memakai proyek terbaru sebagai contoh. Pesan
     * diberi penanda [TES] supaya tidak dikira notifikasi sungguhan.
     */
    public function testNotification(string $step)
    {
        abort_unless(array_key_exists($step, Project::WORKFLOW_STEPS), 404);
        if (! WhatsAppNotifier::isConfigured()) {
            return back()->with('error', 'Lengkapi & aktifkan pengaturan koneksi dulu sebelum tes kirim.');
        }

        $project = Project::latest('id')->first();
        if (! $project) {
            return back()->with('error', 'Belum ada proyek untuk dijadikan contoh pesan.');
        }

        $cfg   = WhatsAppNotification::forStep($step);
        $users = WhatsAppNotifier::recipients($project, (array) $cfg->recipients);
        $text  = "[TES]\n" . WhatsAppNotifier::render($cfg->template, $project, $step, auth()->user(), 'Contoh catatan.', $users);
        $ok    = WhatsAppNotifier::sendToGroup($text, $users, $cfg->group_jid);

        return redirect()->route('settings.whatsapp.edit')->withFragment('notifikasi')->with($ok ? 'success' : 'error', $ok
            ? 'Pesan tes "' . Project::WORKFLOW_STEPS[$step]['title'] . '" terkirim.'
            : 'Pesan tes gagal terkirim. Cek koneksi bot.');
    }
}
