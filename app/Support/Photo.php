<?php

namespace App\Support;

/** Image helpers for generated documents. */
class Photo
{
    /**
     * Centre-crop a photo to 3:4 (the 履歴書 photo box) and return it as
     * JPEG bytes, or null when the file is not a readable image. A
     * transparent background comes out white, as the CV photo requires.
     */
    public static function passportJpeg(string $path): ?string
    {
        $data = @file_get_contents($path);
        $src  = $data !== false ? @imagecreatefromstring($data) : false;
        if (! $src) {
            return null;
        }
        [$w, $h] = [imagesx($src), imagesy($src)];
        $cw = (int) min($w, $h * 3 / 4);
        $ch = (int) min($h, $w * 4 / 3);
        $out = imagecreatetruecolor(450, 600);
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
        imagealphablending($out, true);
        imagecopyresampled($out, $src, 0, 0, (int) (($w - $cw) / 2), (int) (($h - $ch) / 2), 450, 600, $cw, $ch);

        ob_start();
        imagejpeg($out, null, 88);

        return ob_get_clean();
    }
}
