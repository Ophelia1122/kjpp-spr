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

        $bin    = config('kjpp.libreoffice_bin', 'soffice');
        $outDir = dirname($docxPath);
        $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);
        @is_file($pdfPath) && @unlink($pdfPath);

        // Profil LibreOffice unik & sekali pakai -> aman untuk request
        // paralel (tiap konversi tidak saling mengunci).
        $profile = $outDir . '/lo-' . bin2hex(random_bytes(6));
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
            $process = Process::fromShellCommandline($cmd, $outDir, self::env(), null, 120);
        } else {
            $process = new Process($args, $outDir, self::env(), null, 120);
        }

        $process->run();
        self::rrmdir($profile);

        if (!is_file($pdfPath)) {
            throw new RuntimeException(
                'Konversi .docx ke PDF gagal (exit ' . $process->getExitCode() . '). '
                . "Pastikan LibreOffice terpasang & config('kjpp.libreoffice_bin') / env "
                . "LIBREOFFICE_BIN benar.\nCMD: " . $process->getCommandLine()
                . "\nOUT: " . trim($process->getOutput())
                . "\nERR: " . trim($process->getErrorOutput())
            );
        }

        return $pdfPath;
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
