<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Wipes every uploaded file on the public disk and replaces the database
 * references with generated sample media in the "Misi ke Jepang" style
 * (washi paper, hinomaru sun, Fuji, waves, a "CONTOH" label), so the
 * site looks complete without real uploads. Video and music cannot be
 * generated, so those settings are cleared and their sections hide.
 *
 *   php artisan db:seed --class=SampleMediaSeeder
 */
class SampleMediaSeeder extends Seeder
{
    private const PALETTE = [
        'washi'  => [245, 237, 224],
        'kami'   => [255, 250, 242],
        'ink'    => [29, 23, 48],
        'red'    => [179, 37, 16],
        'sakura' => [245, 195, 200],
        'kin'    => [241, 175, 46],
        'ai'     => [43, 71, 149],
        'matcha' => [78, 138, 62],
    ];

    private ?string $latinFont = null;
    private ?string $jpFont = null;

    public function run(): void
    {
        $disk = Storage::disk('public');
        $this->latinFont = $this->firstExisting(['C:/Windows/Fonts/seguibl.ttf', 'C:/Windows/Fonts/arialbd.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf']);
        // GD renders Yu Gothic / MS Gothic .ttc files as tofu; these work.
        $this->jpFont = $this->firstExisting(['C:/Windows/Fonts/BIZ-UDGothicB.ttc', 'C:/Windows/Fonts/NotoSansJP-VF.ttf', '/usr/share/fonts/opentype/noto/NotoSansCJK-Bold.ttc']);

        // 1. Remove every previous upload (keep the disk's .gitignore).
        foreach ($disk->directories() as $dir) {
            $disk->deleteDirectory($dir);
        }
        foreach ($disk->files() as $file) {
            if ($file !== '.gitignore') {
                $disk->delete($file);
            }
        }

        // 2. Website settings.
        // No hero uploads: the homepage falls back to the bundled Japan photos.
        Setting::set('hero.slides', [], 'hero');
        // No login upload: the login page falls back to the bundled Japan photo.
        Setting::set('login.background_path', null, 'login');
        Setting::set('company.profile_pdf_path', $this->pdf('company/company-profile-sample.pdf', [
            'Company Profile (CONTOH)',
            \App\Support\Brand::name(),
            '',
            'Dokumen ini adalah contoh. Unggah company profile asli',
            'lewat Admin > Website Settings > Company.',
        ]), 'company');
        Setting::set('company.video_path', null, 'company');
        Setting::set('company.video_youtube_url', null, 'company');
        Setting::set('company.video_poster_path', null, 'company');
        Setting::set('audio.theme_path', null, 'audio');

        // 3. Gallery albums + items.
        foreach (DB::table('galleries')->get() as $album) {
            $title = $this->tr($album->title);
            DB::table('galleries')->where('id', $album->id)->update([
                'cover_image_path' => $this->scene("gallery/album-{$album->id}-cover.jpg", 1200, 900, $title, 'ギャラリー', $album->id),
            ]);
        }
        foreach (DB::table('gallery_items')->get() as $item) {
            DB::table('gallery_items')->where('id', $item->id)->update([
                'type'        => 'image',
                'image_path'  => $this->scene("gallery/item-{$item->id}.jpg", 1200, 900, $this->tr($item->caption) ?: 'Foto Kegiatan', '写真', $item->id + 3),
                'video_path'  => null,
                'youtube_url' => null,
            ]);
        }

        // 4. Events, blog posts, company logos, job PDFs.
        foreach (DB::table('events')->get() as $e) {
            DB::table('events')->where('id', $e->id)->update([
                'image_path' => $this->scene("events/event-{$e->id}.jpg", 1200, 675, $this->tr($e->title), 'イベント', $e->id + 5),
            ]);
        }
        foreach (DB::table('posts')->get() as $p) {
            DB::table('posts')->where('id', $p->id)->update([
                'thumbnail_path' => $this->scene("posts/post-{$p->id}.jpg", 1200, 675, $this->tr($p->title), 'ブログ', $p->id + 9),
            ]);
        }
        foreach (DB::table('companies')->get() as $c) {
            DB::table('companies')->where('id', $c->id)->update([
                'logo_path' => $this->logo("companies/logo-{$c->id}.png", $c->name, $c->id),
            ]);
        }
        $jobPdf = $this->pdf('jobs/job-detail-sample.pdf', [
            'Detail Lowongan (CONTOH)',
            '',
            'Dokumen ini adalah contoh. Unggah PDF detail lowongan',
            'asli lewat Admin > Job Vacancies.',
        ]);
        DB::table('job_vacancies')->whereNotNull('detail_pdf_path')->update(['detail_pdf_path' => $jobPdf]);

        // Uploads that cannot be replaced meaningfully are cleared.
        DB::table('testimonials')->update(['avatar_path' => null]);
        DB::table('visa_workflow_steps')->update(['icon_path' => null]);

        $this->command?->info('Sample media generated on the public disk.');
    }

    /**
     * Washi scene: sun, Fuji, waves and a hard-shadow "CONTOH" label.
     */
    private function scene(string $path, int $w, int $h, string $label, string $jp, int $seed): string
    {
        mt_srand(crc32($path) + $seed);
        $img = imagecreatetruecolor($w, $h);
        imageantialias($img, true);
        $c = $this->colors($img);
        $bgs = ['washi', 'sakura', 'washi', 'kin', 'washi', 'ai'];
        $bgKey = $bgs[$seed % count($bgs)];
        imagefill($img, 0, 0, $c[$bgKey]);

        // Faint grid.
        [$br, $bg, $bb] = self::PALETTE[$bgKey];
        $grid = imagecolorallocate($img, (int) ($br * .92), (int) ($bg * .92), (int) ($bb * .92));
        for ($x = 0; $x < $w; $x += 48) imageline($img, $x, 0, $x, $h, $grid);
        for ($y = 0; $y < $h; $y += 48) imageline($img, 0, $y, $w, $y, $grid);

        $s = $w / 1600;
        // Sun with ink outline + hard shadow.
        $r = (int) (mt_rand(230, 330) * $s);
        $sx = (int) (mt_rand(55, 78) / 100 * $w);
        $sy = (int) (mt_rand(26, 40) / 100 * $h);
        $off = (int) max(6, 12 * $s);
        imagefilledellipse($img, $sx + $off, $sy + $off, $r * 2, $r * 2, $c['ink']);
        imagefilledellipse($img, $sx, $sy, $r * 2, $r * 2, $c['ink']);
        imagefilledellipse($img, $sx, $sy, $r * 2 - (int) (12 * $s), $r * 2 - (int) (12 * $s), $c['red']);

        // Fuji.
        $base = (int) ($h * 0.86);
        $peakX = (int) ($w * mt_rand(45, 62) / 100);
        $peakY = (int) ($h * 0.36);
        $half = (int) ($w * 0.42);
        $mount = [$peakX - $half, $base, $peakX - (int) (90 * $s), $peakY, $peakX + (int) (90 * $s), $peakY, $peakX + $half, $base];
        imagesetthickness($img, (int) max(3, 6 * $s));
        imagefilledpolygon($img, $mount, $bgKey === 'ai' ? $c['ink'] : $c['ai']);
        imagepolygon($img, $mount, $c['ink']);
        $snow = [
            $peakX - (int) (90 * $s), $peakY, $peakX + (int) (90 * $s), $peakY,
            $peakX + (int) (150 * $s), $peakY + (int) (70 * $s), $peakX + (int) (90 * $s), $peakY + (int) (95 * $s),
            $peakX + (int) (30 * $s), $peakY + (int) (60 * $s), $peakX - (int) (30 * $s), $peakY + (int) (100 * $s),
            $peakX - (int) (90 * $s), $peakY + (int) (65 * $s), $peakX - (int) (150 * $s), $peakY + (int) (70 * $s),
        ];
        imagefilledpolygon($img, $snow, $c['kami']);
        imagepolygon($img, $snow, $c['ink']);

        // Seigaiha waves along the bottom.
        imagefilledrectangle($img, 0, $base, $w, $h, $c['ink']);
        $wr = (int) (60 * $s);
        imagesetthickness($img, 1);
        for ($row = 0; $row < 3; $row++) {
            for ($x = ($row % 2) * $wr; $x < $w + $wr; $x += $wr * 2) {
                $cy = $base + (int) (30 * $s) + $row * (int) (34 * $s);
                foreach ([1, 0.7, 0.4] as $k) {
                    imagefilledarc($img, $x, $cy, (int) ($wr * 2 * $k), (int) ($wr * 2 * $k), 180, 360, $k === 0.7 ? $c['ink'] : $c['kami'], IMG_ARC_PIE);
                }
            }
        }

        // Sakura petals.
        for ($i = 0; $i < 14; $i++) {
            $px = mt_rand(0, $w);
            $py = mt_rand(0, (int) ($h * 0.8));
            $ps = (int) (mt_rand(10, 20) * $s);
            imagefilledellipse($img, $px, $py, $ps * 2, (int) ($ps * 1.3), $c['sakura']);
            imageellipse($img, $px, $py, $ps * 2, (int) ($ps * 1.3), $c['ink']);
        }

        // Label card.
        $this->label($img, $c, (int) (60 * $s), (int) (60 * $s), Str::limit($label, 26), $jp, $s);

        Storage::disk('public')->put($path, $this->encode($img, 'jpg'));
        imagedestroy($img);

        return $path;
    }

    private function logo(string $path, string $name, int $seed): string
    {
        $size = 400;
        $img = imagecreatetruecolor($size, $size);
        imageantialias($img, true);
        $c = $this->colors($img);
        imagefill($img, 0, 0, $c['kami']);
        $fills = ['red', 'ai', 'matcha', 'kin', 'sakura'];
        $fill = $fills[$seed % count($fills)];
        imagefilledellipse($img, 212, 212, 320, 320, $c['ink']);
        imagefilledellipse($img, 200, 200, 320, 320, $c['ink']);
        imagefilledellipse($img, 200, 200, 304, 304, $c[$fill]);
        $initials = Str::of($name)->replaceMatches('/^(PT|CV)\.?\s+/i', '')->explode(' ')->filter()->take(2)
            ->map(fn ($w) => Str::upper(Str::substr($w, 0, 1)))->implode('');
        $text = in_array($fill, ['kin', 'sakura'], true) ? $c['ink'] : $c['kami'];
        if ($this->latinFont) {
            $box = imagettfbbox(110, 0, $this->latinFont, $initials);
            $tw = $box[2] - $box[0];
            $th = $box[1] - $box[7];
            imagettftext($img, 110, 0, (int) (200 - $tw / 2), (int) (200 + $th / 2), $text, $this->latinFont, $initials);
        } else {
            imagestring($img, 5, 185, 190, $initials, $text);
        }
        Storage::disk('public')->put($path, $this->encode($img, 'png'));
        imagedestroy($img);

        return $path;
    }

    private function label($img, array $c, int $x, int $y, string $title, string $jp, float $s): void
    {
        $fs = max(18, (int) (54 * $s));
        $tagFs = max(10, (int) (20 * $s));
        $pad = (int) (28 * $s);
        $tag = 'CONTOH · SAMPLE';
        $tw = $this->latinFont ? abs(imagettfbbox($fs, 0, $this->latinFont, $title)[2]) : strlen($title) * 9;
        $tagW = $this->latinFont ? abs(imagettfbbox($tagFs, 0, $this->latinFont, $tag)[2]) : strlen($tag) * 9;
        $jw = $this->jpFont ? abs(imagettfbbox($tagFs, 0, $this->jpFont, $jp)[2]) : 0;
        $boxW = max($tw, $tagW + $jw + $pad) + $pad * 2;
        $boxH = $fs + $tagFs + $pad * 3;
        $off = (int) max(6, 12 * $s);
        $border = (int) max(3, 6 * $s);
        imagefilledrectangle($img, $x + $off, $y + $off, $x + $boxW + $off, $y + $boxH + $off, $c['ink']);
        imagefilledrectangle($img, $x, $y, $x + $boxW, $y + $boxH, $c['ink']);
        imagefilledrectangle($img, $x + $border, $y + $border, $x + $boxW - $border, $y + $boxH - $border, $c['kami']);

        if ($this->latinFont) {
            imagettftext($img, $tagFs, 0, $x + $pad, $y + $pad + $tagFs, $c['red'], $this->latinFont, $tag);
            imagettftext($img, $fs, 0, $x + $pad, $y + $pad * 2 + $tagFs + $fs, $c['ink'], $this->latinFont, $title);
        } else {
            imagestring($img, 5, $x + $pad, $y + $pad, $tag, $c['red']);
            imagestring($img, 5, $x + $pad, $y + $pad * 2 + 16, $title, $c['ink']);
        }
        if ($this->jpFont) {
            imagettftext($img, $tagFs, 0, $x + $boxW - $pad - $jw, $y + $pad + $tagFs, $c['ink'], $this->jpFont, $jp);
        }
    }

    private function colors($img): array
    {
        $out = [];
        foreach (self::PALETTE as $k => [$r, $g, $b]) {
            $out[$k] = imagecolorallocate($img, $r, $g, $b);
        }

        return $out;
    }

    private function encode($img, string $type): string
    {
        ob_start();
        $type === 'png' ? imagepng($img, null, 8) : imagejpeg($img, null, 85);

        return (string) ob_get_clean();
    }

    /**
     * Minimal one-page PDF (Helvetica text lines) — no PDF library needed.
     */
    private function pdf(string $path, array $lines): string
    {
        $esc = fn ($t) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $t);
        $stream = "BT /F1 26 Tf 72 760 Td (".$esc(array_shift($lines)).") Tj ET\n";
        $y = 720;
        foreach ($lines as $line) {
            $stream .= "BT /F1 13 Tf 72 {$y} Td (".$esc($line).") Tj ET\n";
            $y -= 22;
        }
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            "<< /Length ".strlen($stream)." >>\nstream\n{$stream}endstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$obj}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
        Storage::disk('public')->put($path, $pdf);

        return $path;
    }

    private function tr(?string $json): string
    {
        $v = json_decode((string) $json, true);
        if (is_array($v)) {
            return (string) ($v['id'] ?? $v['en'] ?? reset($v) ?? '');
        }

        return (string) $json;
    }

    private function firstExisting(array $paths): ?string
    {
        foreach ($paths as $p) {
            if (is_file($p)) {
                return $p;
            }
        }

        return null;
    }
}
