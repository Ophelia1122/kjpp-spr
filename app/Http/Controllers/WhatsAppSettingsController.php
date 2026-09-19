<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\AppSetting;
use App\Services\WhatsAppNotifier;
use Illuminate\Http\Request;

/** Pengaturan Sistem > Bot WhatsApp (2026-09-14, feedback user). */
class WhatsAppSettingsController extends Controller
{
    public function edit()
    {
        $settings = collect(WhatsAppNotifier::KEYS)->mapWithKeys(fn ($k) => [$k => WhatsAppNotifier::setting($k)]);

        return view('settings.whatsapp', [
            'settings'   => $settings,
            'configured' => WhatsAppNotifier::isConfigured(),
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
}
