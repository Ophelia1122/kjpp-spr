<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Konversi .docx -> .pdf memakai LibreOffice headless (soffice).
 *
 * Word (.docx dari ProposalDocxBuilder) = MASTER; PDF = hasil render
 * LibreOffice atas master tsb, sehingga tata letak Word == PDF.
 *
 * Path binary: config('kjpp.libreoffice_bin') (env LIBREOFFICE_BIN).
 * Docker/Linux: 'soffice'. Windows lokal: path lengkap ke soffice.exe.
 */
class DocxToPdf
{
    /** @return string path file .pdf hasil konversi (folder yang sama). */
    public static function convert(string $docxPath): string
    {
        if (!is_file($docxPath)) {
            throw new RuntimeException("File .docx tidak ditemukan: {$docxPath}");
        }

        $bin    = self::resolveBin(config('kjpp.libreoffice_bin', 'soffice'));
        $outDir = dirname($docxPath);
        $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);
        @is_file($pdfPath) && @unlink($pdfPath);

        // Windows: konversi headless sebelumnya sering meninggalkan proses
        // soffice.bin "zombie" yang menangkap perintah headless berikutnya
        // (exit 0, tapi PDF tidak dibuat). Bersihkan dulu — hanya proses
        // yang command-line-nya headless+convert-to, jadi LibreOffice yang
        // dibuka user untuk mengedit dokumen TIDAK ikut ditutup.
        if (PHP_OS_FAMILY === 'Windows') {
            self::reapHeadlessSoffice();
        }

        $process = self::runSoffice($bin, $outDir, $docxPath);

        // Retry sekali (Windows): percobaan pertama kadang gagal kalau
        // instance headless lama baru saja dimatikan / belum benar-benar lepas.
        if (!is_file($pdfPath) && PHP_OS_FAMILY === 'Windows') {
            self::reapHeadlessSoffice();
            usleep(1_500_000);
            $process = self::runSoffice($bin, $outDir, $docxPath);
        }

        if (!is_file($pdfPath)) {
            $hint = '';
            if (PHP_OS_FAMILY === 'Windows') {
                $hint = "\nKalau ini terjadi berulang di lokal: tutup SEMUA jendela "
                    . "LibreOffice (cek Task Manager: soffice.bin) lalu ulangi. "
                    . "Di server produksi hal ini tidak terjadi.";
            }

            throw new RuntimeException(
                'Konversi .docx ke PDF gagal (exit ' . $process->getExitCode() . '). '
                . "Pastikan LibreOffice terpasang & config('kjpp.libreoffice_bin') / env "
                . "LIBREOFFICE_BIN benar." . $hint
                . "\nCMD: " . $process->getCommandLine()
                . "\nOUT: " . trim($process->getOutput())
                . "\nERR: " . trim($process->getErrorOutput())
            );
        }

        return $pdfPath;
    }

    /** Jalankan satu percobaan konversi soffice. */
    private static function runSoffice(string $bin, string $outDir, string $docxPath): Process
    {
        // Profil UserInstallation TERPISAH & PERSISTEN (bukan profil default
        // LibreOffice milik user). Sekali "warm-up" (bootstrap agak lama di
        // pemakaian pertama), konversi berikutnya cepat & tidak rebutan lock
        // dengan LibreOffice yang mungkin sedang dibuka user. Folder ini
        // gitignored (storage/app/.gitignore).
        $profile = storage_path('app/lo-profile');
        @mkdir($profile, 0775, true);
        $profileUri = 'file:///' . str_replace('\\', '/', $profile);

        $args = [
            $bin,
            '-env:UserInstallation=' . $profileUri,
            '--headless', '--norestore', '--nologo', '--nofirststartwizard',
            '--convert-to', 'pdf',
            '--outdir', $outDir,
            $docxPath,
        ];

        // Di Windows, executable dengan spasi di path ('C:\Program Files\...')
        // lebih andal dijalankan lewat shell command-line ketimbang argv +
        // bypass_shell. Di Linux/Docker argv biasa sudah cukup.
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = implode(' ', array_map(
                static fn ($a) => '"' . str_replace('"', '""', $a) . '"',
                $args
            ));
            $process = Process::fromShellCommandline($cmd, $outDir, self::env(), null, 180);
        } else {
            $process = new Process($args, $outDir, self::env(), null, 180);
        }

        $process->run();

        return $process;
    }

    /**
     * Windows: matikan proses soffice yang jelas-jelas dari konversi headless
     * (bukan LibreOffice interaktif milik user).
     */
    private static function reapHeadlessSoffice(): void
    {
        $ps = 'Get-CimInstance Win32_Process -Filter "Name=\'soffice.exe\' OR Name=\'soffice.bin\'" '
            . '| Where-Object { $_.CommandLine -like \'*--headless*\' -and $_.CommandLine -like \'*--convert-to*\' } '
            . '| ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }';

        $p = Process::fromShellCommandline('powershell -NoProfile -NonInteractive -Command "' . $ps . '"', null, self::env(), null, 15);
        $p->run();
        usleep(300_000);
    }

    /**
     * Di Windows, soffice.exe adalah launcher GUI yang bisa "return" sebelum
     * konversi selesai. soffice.com (console) memblokir sampai selesai &
     * melaporkan hasil dengan benar — pakai itu bila ada.
     */
    private static function resolveBin(string $bin): string
    {
        if (PHP_OS_FAMILY === 'Windows' && preg_match('/soffice\.exe$/i', $bin)) {
            $com = preg_replace('/soffice\.exe$/i', 'soffice.com', $bin);
            if (is_file($com)) {
                return $com;
            }
        }
        return $bin;
    }

    /** Env minimal + variabel Windows yang dibutuhkan soffice. */
    private static function env(): array
    {
        $keep = [
            'SystemRoot', 'windir', 'TEMP', 'TMP', 'PATH', 'PATHEXT',
            'USERPROFILE', 'APPDATA', 'LOCALAPPDATA', 'HOMEDRIVE', 'HOMEPATH',
            'PROGRAMFILES', 'PROGRAMFILES(X86)', 'PROGRAMDATA', 'COMMONPROGRAMFILES',
            'NUMBER_OF_PROCESSORS', 'HOME', 'LANG', 'LC_ALL',
        ];
        $env = [];
        foreach ($keep as $k) {
            $v = getenv($k);
            if ($v !== false) {
                $env[$k] = $v;
            }
        }
        return $env;
    }

    private static function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = @scandir($dir) ?: [];
        foreach ($items as $it) {
            if ($it === '.' || $it === '..') {
                continue;
            }
            $p = $dir . DIRECTORY_SEPARATOR . $it;
            is_dir($p) ? self::rrmdir($p) : @unlink($p);
        }
        @rmdir($dir);
    }
}
