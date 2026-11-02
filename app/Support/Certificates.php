<?php

namespace App\Support;

/**
 * The licences & certificates a student can pick on the profile, in three
 * groups. Each entry is [label, name on the 履歴書]; the label is shown on
 * the id/en site (translated through lang/*.json), the Japanese name on
 * the ja site and in the CV. Picks are stored in student_profiles.licenses
 * as {group, type, name?, score?, year, month}.
 */
class Certificates
{
    public const GROUPS = [
        'language' => [
            'jft_a2'  => ['JFT A2', '国際交流基金日本語基礎テストA2'],
            'jlpt_n4' => ['JLPT N4', '日本語能力試験N4'],
            'jlpt_n3' => ['JLPT N3', '日本語能力試験N3'],
            'jlpt_n2' => ['JLPT N2', '日本語能力試験N2'],
            'jlpt_n1' => ['JLPT N1', '日本語能力試験N1'],
        ],
        'ssw' => [
            'ssw_truck'         => ['SSW Truck driver', '自動車運送業トラック分野評価試験'],
            'ssw_taxi'          => ['SSW Taxi driver', '自動車運送業タクシー分野評価試験'],
            'ssw_bus'           => ['SSW Bus driver', '自動車運送業バス分野評価試験'],
            'ssw_care'          => ['SSW Caregiving skills', '介護特定技能評価試験'],
            'ssw_care_japanese' => ['SSW Caregiving Japanese', '介護日本語評価試験'],
            'ssw_construction'  => ['SSW Construction', '建設分野特定技能1号評価試験'],
            'ssw_manufacturing' => ['SSW Manufacturing', '製造分野特定技能1号評価試験'],
            'ssw_food'          => ['SSW Food processing', '飲食料品製造業特定技能1号技能測定試験'],
            'ssw_agriculture'   => ['SSW Agriculture', '農業特定技能1号技能測定試験'],
            'ssw_livestock'     => ['SSW Livestock', '農業特定技能1号技能測定試験'],
            'ssw_cleaning'      => ['SSW Building cleaning', 'ビルクリーニング分野特定技能1号評価試験'],
            'ssw_hotel'         => ['SSW Hospitality', '宿泊業特定技能1号測定試験'],
            'ssw_automotive'    => ['SSW Automotive maintenance', '自動車整備分野特定技能1号評価試験'],
        ],
        'other' => [
            'sim_a'  => ['SIM A', 'インドネシアA運転免許証'],
            'sim_b1' => ['SIM B1', 'インドネシアB1運転免許証'],
            'toefl'  => ['TOEFL', 'TOEFL'],
            'ielts'  => ['IELTS', 'IELTS'],
            'other'  => ['Other', null],
        ],
    ];

    /** Types that need a score instead of a pass. */
    public const SCORED = ['toefl', 'ielts'];

    /** Japanese level for the CV's 日本語力 box, best first. */
    private const JAPANESE_RANK = ['jlpt_n1', 'jlpt_n2', 'jlpt_n3', 'jlpt_n4', 'jft_a2'];

    /** Dropdown options of one group in the current site language. */
    public static function options(string $group): array
    {
        $ja = app()->getLocale() === 'ja';

        return collect(self::GROUPS[$group])
            ->map(fn ($c) => $ja && $c[1] ? $c[1] : __($c[0]))
            ->all();
    }

    /** The group a certificate type belongs to ("language", "ssw", "other"). */
    public static function groupOf(string $type): ?string
    {
        foreach (self::GROUPS as $group => $types) {
            if (isset($types[$type])) {
                return $group;
            }
        }

        return null;
    }

    /** Short label of a certificate type in the current site language. */
    public static function label(string $type): string
    {
        $group = self::groupOf($type);

        return $group ? self::options($group)[$type] : $type;
    }

    /** Stored entries split by group, for the profile form. Old free-text licences land under "Other". */
    public static function byGroup(?array $licenses): array
    {
        $groups = array_fill_keys(array_keys(self::GROUPS), []);
        foreach ($licenses ?? [] as $item) {
            if (! isset($item['type'])) {
                $item = ['group' => 'other', 'type' => 'other'] + $item;
            }
            $groups[$item['group'] ?? 'other'][] = $item;
        }

        return $groups;
    }

    /** Form groups back to the stored list. */
    public static function flatten(array $groups): array
    {
        return collect($groups)
            ->flatMap(fn ($items, $group) => collect($items)
                ->filter(fn ($item) => filled($item['type'] ?? null))
                ->map(fn ($item) => array_filter([
                    'group' => $group,
                    'type'  => $item['type'],
                    'name'  => $item['type'] === 'other' ? ($item['name'] ?? null) : null,
                    'score' => in_array($item['type'], self::SCORED, true) ? ($item['score'] ?? null) : null,
                    'year'  => $item['year'] ?? null,
                    'month' => $item['month'] ?? null,
                ], fn ($v) => $v !== null && $v !== '')))
            ->values()->all();
    }

    /**
     * How an entry reads on the CV, in the agency's style:
     * "日本語能力試験N3　合格", "TOEFL　スコア550", or the name typed for "Other".
     */
    public static function cvText(array $item): ?string
    {
        $type = $item['type'] ?? 'other';
        if ($type === 'other') {
            return filled($item['name'] ?? null) ? $item['name'] : null;
        }
        $name = self::GROUPS[$item['group'] ?? 'other'][$type][1] ?? null;
        if (! $name) {
            return null;
        }

        return in_array($type, self::SCORED, true)
            ? $name.(filled($item['score'] ?? null) ? '　スコア'.$item['score'] : '')
            : $name.'　合格';
    }

    /** Best Japanese certificate as a short code ("JLPT N3", "JFT A2"), or null. */
    public static function japaneseLevel(?array $licenses): ?string
    {
        $types = collect($licenses ?? [])->pluck('type')->all();
        foreach (self::JAPANESE_RANK as $type) {
            if (in_array($type, $types, true)) {
                return self::GROUPS['language'][$type][0];
            }
        }

        return null;
    }

    /** English test scores for the CV's その他語学力 box: "英語（TOEFL 550）". */
    public static function otherLanguages(?array $licenses): ?string
    {
        $scores = collect($licenses ?? [])
            ->filter(fn ($item) => in_array($item['type'] ?? null, self::SCORED, true))
            ->map(fn ($item) => self::GROUPS['other'][$item['type']][0].(filled($item['score'] ?? null) ? ' '.$item['score'] : ''));

        return $scores->isEmpty() ? null : '英語（'.$scores->implode('、').'）';
    }
}
