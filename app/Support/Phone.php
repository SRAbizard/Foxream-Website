<?php

namespace App\Support;

/** A phone number as typed or pasted, made plain: ASCII digits, + and - only, single spaces. */
class Phone
{
    public static function clean(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }
        $phone = mb_convert_kana($phone, 'as');                            // full-width digits, + and spaces → ASCII
        $phone = preg_replace('/\p{Cf}/u', '', $phone);                     // invisible direction marks
        $phone = preg_replace('/[－‐―ー]/u', '-', $phone);                  // Japanese dashes
        $phone = strtr($phone, ['（' => '(', '）' => ')']);
        $phone = trim(preg_replace('/\s+/u', ' ', $phone));

        return $phone === '' ? null : $phone;
    }
}
