<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

/**
 * The 履歴書 as PDF. When LibreOffice is available (LIBREOFFICE_BIN, or a
 * standard install path) the filled Word file itself is converted, so
 * the PDF is identical to the .docx. Otherwise mPDF draws the same layout
 * in the template's font (BIZ UDMincho) as a close preview.
 */
class RirekishoPdf
{
    public static function render(User $user, ?string $desiredJob = null): string
    {
        if ($soffice = self::libreOffice()) {
            try {
                return self::convert(RirekishoDocx::render($user, $desiredJob), $soffice);
            } catch (Throwable $e) {
                report($e);   // fall back to the mPDF preview
            }
        }

        return self::preview(Rirekisho::data($user, $desiredJob));
    }

    public static function libreOffice(): ?string
    {
        $candidates = array_filter([
            config('site.libreoffice'),
            '/usr/bin/soffice', '/usr/bin/libreoffice', '/usr/local/bin/soffice',
            '/Applications/LibreOffice.app/Contents/MacOS/soffice',
            'C:\Program Files\LibreOffice\program\soffice.exe',
        ]);
        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /** DOCX bytes → PDF bytes through headless LibreOffice. */
    private static function convert(string $docx, string $soffice): string
    {
        $dir = storage_path('app/mpdf/lo-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($dir);
        try {
            file_put_contents("{$dir}/rirekisho.docx", $docx);
            $result = Process::timeout(90)->path($dir)->env(['HOME' => $dir])->run([
                $soffice, '--headless', '--norestore',
                '-env:UserInstallation=file:///'.str_replace('\\', '/', "{$dir}/profile"),
                '--convert-to', 'pdf', '--outdir', $dir, "{$dir}/rirekisho.docx",
            ]);
            if (! $result->successful() || ! is_file("{$dir}/rirekisho.pdf")) {
                throw new RuntimeException('LibreOffice conversion failed: '.$result->errorOutput());
            }

            return file_get_contents("{$dir}/rirekisho.pdf");
        } finally {
            File::deleteDirectory($dir);
        }
    }

    /** The same layout drawn with mPDF (resources/views/pdf/rirekisho.blade.php). */
    private static function preview(array $cv): string
    {
        $photo = null;
        if ($cv['photo'] && ($jpeg = Photo::passportJpeg($cv['photo']))) {
            File::ensureDirectoryExists(storage_path('app/mpdf'));
            $photo = storage_path('app/mpdf/cv-'.bin2hex(random_bytes(6)).'.jpg');
            file_put_contents($photo, $jpeg);
        }
        $cv['photo'] = $photo;

        $mpdf = Pdf::make([
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 12,
            'margin_bottom' => 16,
            'margin_footer' => 7,
        ]);
        $mpdf->SetTitle('履歴書 — '.$cv['name']);
        $mpdf->SetAuthor(\App\Support\Brand::company());
        $mpdf->SetHTMLFooter('<table width="100%" style="border-top: 0.6pt solid #999; font-family: bizmincho; font-size: 7.5pt;"><tr>'
            .'<td style="padding-top: 3pt; color: #9E1B32; font-weight: bold;">'.e(mb_strtoupper(\App\Support\Brand::name())).'</td>'
            .'<td style="padding-top: 3pt; text-align: right; color: #999;">{PAGENO} / {nbpg}</td></tr></table>');

        try {
            $mpdf->WriteHTML(view('pdf.rirekisho', ['cv' => $cv])->render());

            return $mpdf->OutputBinaryData();
        } finally {
            if ($photo) {
                @unlink($photo);
            }
        }
    }
}
