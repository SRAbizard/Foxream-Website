<?php

namespace App\Support;

/**
 * Indonesian names written in katakana by how they sound, for the CV's
 * furigana line: "Budi Santoso" → "ブディ　サントソ". Names are not sent
 * to DeepL, which translates the ones that are also words ("Budi" → 善徳,
 * "Dewi" → 女神).
 *
 * Indonesian spelling is close to how it sounds, so the name is cut into
 * consonant + vowel syllables: ng / ny / sy / kh are one sound, a
 * consonant without a vowel gets the u (o after t, d) a Japanese ear
 * hears, a final n is ン, a final h is dropped, and a doubled consonant
 * becomes ッ (ン before m and n). Old spellings (dj, tj, oe) are read as today.
 */
class Katakana
{
    /** Consonant => kana for a, i, u, e, o. */
    private const ROWS = [
        ''   => ['ア', 'イ', 'ウ', 'エ', 'オ'],
        'k'  => ['カ', 'キ', 'ク', 'ケ', 'コ'],
        'g'  => ['ガ', 'ギ', 'グ', 'ゲ', 'ゴ'],
        's'  => ['サ', 'シ', 'ス', 'セ', 'ソ'],
        'z'  => ['ザ', 'ジ', 'ズ', 'ゼ', 'ゾ'],
        'j'  => ['ジャ', 'ジ', 'ジュ', 'ジェ', 'ジョ'],
        'c'  => ['チャ', 'チ', 'チュ', 'チェ', 'チョ'],
        't'  => ['タ', 'ティ', 'トゥ', 'テ', 'ト'],
        'd'  => ['ダ', 'ディ', 'ドゥ', 'デ', 'ド'],
        'n'  => ['ナ', 'ニ', 'ヌ', 'ネ', 'ノ'],
        'h'  => ['ハ', 'ヒ', 'フ', 'ヘ', 'ホ'],
        'f'  => ['ファ', 'フィ', 'フ', 'フェ', 'フォ'],
        'v'  => ['ファ', 'フィ', 'フ', 'フェ', 'フォ'],
        'b'  => ['バ', 'ビ', 'ブ', 'ベ', 'ボ'],
        'p'  => ['パ', 'ピ', 'プ', 'ペ', 'ポ'],
        'm'  => ['マ', 'ミ', 'ム', 'メ', 'モ'],
        'y'  => ['ヤ', 'イ', 'ユ', 'イェ', 'ヨ'],
        'r'  => ['ラ', 'リ', 'ル', 'レ', 'ロ'],
        'l'  => ['ラ', 'リ', 'ル', 'レ', 'ロ'],
        'w'  => ['ワ', 'ウィ', 'ウ', 'ウェ', 'ウォ'],
        'ng' => ['ンガ', 'ンギ', 'ング', 'ンゲ', 'ンゴ'],
        'ny' => ['ニャ', 'ニ', 'ニュ', 'ニェ', 'ニョ'],
        'sy' => ['シャ', 'シ', 'シュ', 'シェ', 'ショ'],
    ];

    /** Kana for a consonant with no vowel after it. */
    private const BARE = [
        'k' => 'ク', 'g' => 'グ', 's' => 'ス', 'z' => 'ズ', 'j' => 'ジ', 'c' => 'チ',
        't' => 'ト', 'd' => 'ド', 'n' => 'ン', 'h' => '', 'f' => 'フ', 'v' => 'フ',
        'b' => 'ブ', 'p' => 'プ', 'm' => 'ム', 'y' => 'イ', 'r' => 'ル', 'l' => 'ル',
        'w' => 'ウ', 'ng' => 'ン', 'ny' => 'ニ', 'sy' => 'シ',
    ];

    private const VOWELS = ['a' => 0, 'i' => 1, 'u' => 2, 'e' => 3, 'o' => 4];

    /** A full name: words joined with a full-width space. */
    public static function fromName(?string $name): ?string
    {
        $words = preg_split('/[\s\-]+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
        $kana  = array_filter(array_map([self::class, 'word'], $words));

        return $kana ? implode('　', $kana) : null;
    }

    private static function word(string $word): string
    {
        $w = strtolower(preg_replace('/[^a-z]/i', '', \Illuminate\Support\Str::ascii($word)));
        // Old spellings and letters Indonesian names borrow.
        $w = strtr($w, ['dj' => 'j', 'tj' => 'c', 'oe' => 'u', 'ch' => 'c', 'kh' => 'k', 'sh' => 'sy', 'ph' => 'f', 'th' => 't', 'q' => 'k', 'x' => 'ks']);
        // y after a consonant (Rizky, Andy) or at the end is the vowel i.
        $w = preg_replace('/(?<=[^aiueo])y(?![aiueo])/', 'i', $w);

        $out = '';
        $i = 0;
        $len = strlen($w);
        while ($i < $len) {
            $c = '';
            foreach (['ng', 'ny', 'sy'] as $pair) {
                if (substr($w, $i, 2) === $pair) {
                    $c = $pair;
                    break;
                }
            }
            if ($c === '' && ! isset(self::VOWELS[$w[$i]])) {
                $c = $w[$i];
            }
            $next = $w[$i + strlen($c)] ?? null;

            if ($c !== '' && $next !== null && $next === $c && ! isset(self::VOWELS[$next])) {
                // Doubled consonant: Muhammad → ムハンマド, Abdullah → アブドゥッラ
                $out .= in_array($c, ['m', 'n'], true) ? 'ン' : 'ッ';
                $i += strlen($c);

                continue;
            }
            if ($next !== null && isset(self::VOWELS[$next])) {
                $out .= (self::ROWS[$c] ?? self::ROWS[''])[self::VOWELS[$next]];
                $i += strlen($c) + 1;
            } elseif ($c === '') {
                $out .= self::ROWS[''][self::VOWELS[$w[$i]]];
                $i++;
            } else {
                $out .= match (true) {
                    $c === 'm' && in_array($next, ['b', 'p'], true) => 'ン',  // Bambang → バンバン
                    $c === 'h' && $next !== null                    => 'フ',  // Ahmad → アフマド
                    default                                         => self::BARE[$c] ?? '',
                };
                $i += strlen($c);
            }
        }

        return $out;
    }
}
