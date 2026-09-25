<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Project;
use App\Models\ProposalSectionDefault;
use App\Services\ProposalDocxBuilder;
use Illuminate\Http\Request;

/**
 * =============================================================================
 * PENGATURAN SISTEM > TEKS BAKU PROPOSAL PER TUJUAN PENILAIAN
 *
 * Administrator / General Admin menulis teks default tiap bab proposal untuk
 * masing-masing tujuan penilaian (Jual Beli, Penjaminan Utang, Lelang,
 * Pelaporan Keuangan) plus satu tab "Semua Tujuan". Kalau klausul berubah,
 * teksnya cukup diubah di sini — tidak perlu menyentuh kode.
 *
 * Urutan pemakaian teks saat proposal dicetak:
 *   override per proyek > teks baku tujuan itu > teks baku semua tujuan >
 *   config/proposal_clauses.php.
 * =============================================================================
 */
class ProposalDefaultsController extends Controller
{
    /** Tab yang tersedia: '' = semua tujuan, sisanya per tujuan penilaian. */
    private const TUJUAN = [
        ProposalSectionDefault::SEMUA_TUJUAN => 'Semua Tujuan',
        Project::PURPOSE_JUAL_BELI           => Project::PURPOSE_JUAL_BELI,
        Project::PURPOSE_PENJAMINAN_UTANG    => Project::PURPOSE_PENJAMINAN_UTANG,
        Project::PURPOSE_LELANG              => Project::PURPOSE_LELANG,
        Project::PURPOSE_LK_PROPERTI         => Project::PURPOSE_LK_PROPERTI,
    ];

    public function index(Request $request)
    {
        $tujuan = $this->tujuan($request->query('tujuan'));

        $tersimpan = ProposalSectionDefault::where('proposal_purpose', $tujuan)
            ->pluck('body', 'section_key')->all();

        return view('settings.proposal-defaults', [
            'tujuanAktif'  => $tujuan,
            'daftarTujuan' => self::TUJUAN,
            'sections'     => ProposalDocxBuilder::sectionsForDefaultEditor($tujuan, $tersimpan),
            'jumlahDiisi'  => count($tersimpan),
            'placeholder'  => ProposalDocxBuilder::PLACEHOLDER,
            // Berapa banyak proyek yang teksnya masih ditimpa manual per proyek
            // — supaya admin tahu perubahan di sini tidak mengenai proyek itu.
            'jumlahTimpa'  => \App\Models\ProposalSectionText::distinct('project_id')->count('project_id'),
        ]);
    }

    public function update(Request $request, string $key)
    {
        $tujuan = $this->tujuan($request->input('tujuan'));
        $bab    = $this->bab($tujuan, $key);

        $data = $request->validate(['body' => 'required|string|max:20000']);

        ProposalSectionDefault::updateOrCreate(
            ['proposal_purpose' => $tujuan, 'section_key' => $key],
            ['body' => $data['body'], 'updated_by_user_id' => $request->user()->id],
        );

        AuditLogger::record(
            'proposal_default.saved',
            "Mengubah teks baku bab \"{$bab['title']}\" untuk tujuan penilaian \"{$this->label($tujuan)}\"",
        );

        return $this->kembali($tujuan, $key, "Teks baku bab \"{$bab['title']}\" disimpan.");
    }

    public function reset(Request $request, string $key)
    {
        $tujuan = $this->tujuan($request->input('tujuan'));
        $bab    = $this->bab($tujuan, $key);

        $terhapus = ProposalSectionDefault::where('proposal_purpose', $tujuan)
            ->where('section_key', $key)->delete();

        if ($terhapus) {
            AuditLogger::record(
                'proposal_default.reset',
                "Menghapus teks baku bab \"{$bab['title']}\" untuk tujuan penilaian \"{$this->label($tujuan)}\"",
            );
        }

        return $this->kembali($tujuan, $key, "Teks bab \"{$bab['title']}\" kembali memakai teks bawaan sistem.");
    }

    public function resetAll(Request $request)
    {
        $tujuan = $this->tujuan($request->input('tujuan'));

        $jumlah = ProposalSectionDefault::where('proposal_purpose', $tujuan)->count();
        ProposalSectionDefault::where('proposal_purpose', $tujuan)->delete();

        if ($jumlah) {
            AuditLogger::record(
                'proposal_default.reset_all',
                "Menghapus SELURUH teks baku ({$jumlah} bab) untuk tujuan penilaian \"{$this->label($tujuan)}\"",
            );
        }

        return redirect()
            ->route('settings.proposalDefaults.index', ['tujuan' => $tujuan])
            ->with('success', "Seluruh teks baku tujuan \"{$this->label($tujuan)}\" dikembalikan ke bawaan sistem.");
    }

    /** Validasi tab tujuan penilaian dari querystring/form. */
    private function tujuan(?string $tujuan): string
    {
        $tujuan = (string) $tujuan;

        abort_unless(array_key_exists($tujuan, self::TUJUAN), 404);

        return $tujuan;
    }

    private function label(string $tujuan): string
    {
        return self::TUJUAN[$tujuan];
    }

    /** Pastikan bab itu memang boleh diisi untuk tujuan tsb. */
    private function bab(string $tujuan, string $key): array
    {
        abort_unless(in_array($key, ProposalDocxBuilder::defaultSectionKeys($tujuan), true), 404);

        foreach (ProposalDocxBuilder::sectionsForDefaultEditor($tujuan) as $bab) {
            if ($bab['key'] === $key) {
                return $bab;
            }
        }

        abort(404);
    }

    private function kembali(string $tujuan, string $key, string $pesan)
    {
        return redirect()
            ->route('settings.proposalDefaults.index', ['tujuan' => $tujuan])
            ->with('success', $pesan)
            ->withFragment('bab-' . $key);
    }
}
