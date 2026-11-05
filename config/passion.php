<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    | Indonesia (id) is the default. Adding a new locale here propagates to the
    | language switcher, the SetLocale middleware whitelist, and any UI list.
    */
    'locales' => [
        'id' => ['label' => 'Bahasa Indonesia', 'native' => 'Indonesia'],
        'en' => ['label' => 'English',           'native' => 'English'],
        'ja' => ['label' => '日本語',             'native' => '日本語'],
    ],

    'default_locale' => 'id',

    /*
    | Roles seeded by RolesAndPermissionsSeeder. The first one in the list is
    | considered the most privileged.
    */
    'roles' => ['superadmin', 'admin', 'student'],

    /*
    | First superadmin credentials, used by DatabaseSeeder for local bootstrap.
    | Override via env in any non-local environment.
    */
    'bootstrap_admin' => [
        'name'     => env('BOOTSTRAP_ADMIN_NAME', 'Super Admin'),
        'email'    => env('BOOTSTRAP_ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('BOOTSTRAP_ADMIN_PASSWORD', 'password'),
    ],

    /*
    | Public-facing contact / company info — used in footer, WhatsApp float,
    | meta tags. Migrate to Setting model in Phase 3 (Website Settings CMS).
    */
    // Path to LibreOffice (soffice). When set — or found in a standard
    // install path — the 履歴書 PDF is converted from the filled Word file,
    // so it matches the .docx exactly. Needs the BIZ UDMincho font installed
    // on the server (copy resources/fonts/BIZUDMincho-*.ttf to its fonts).
    'libreoffice' => env('LIBREOFFICE_BIN'),

    'contact' => [
        'whatsapp'  => '62882007885021',
        'phone'     => '+62 882-0078-85021',
        'email'     => null,
        'instagram' => null,
        'facebook'  => null,
        'tiktok'    => null,
        'offices'   => [
            ['city' => 'Yokohama', 'country' => 'Japan',     'address' => 'Yokohama, Kanagawa, Japan'],
            ['city' => 'Brebes',   'country' => 'Indonesia', 'address' => 'Brebes, Jawa Tengah, Indonesia'],
            ['city' => 'Yogyakarta','country' => 'Indonesia','address' => 'Yogyakarta, DI Yogyakarta, Indonesia'],
        ],
    ],

    /*
    | Headline stats shown on the homepage. Phase 3 will pull live counts.
    */
    'stats' => [
        'students'  => '3,300+',
        'workers'   => '1,200+',
        'companies' => '100+',
    ],

    /*
    | Japan's eight regions, used by the homepage job map / finder and the
    | ?region= filter on /jobs. A vacancy's location_prefecture (or, when
    | empty, location_city) is matched against these lists. `x`/`y` place
    | the pin on the homepage map's 400×440 SVG; `airport` feeds the
    | hero boarding pass.
    */
    'regions' => [
        'hokkaido' => ['name' => 'Hokkaido', 'jp' => '北海道', 'x' => 330, 'y' => 64,  'airport' => ['CTS', 'Sapporo'],
            'prefectures' => ['Hokkaido'], 'cities' => ['Sapporo', 'Asahikawa', 'Hakodate']],
        'tohoku'   => ['name' => 'Tohoku',   'jp' => '東北',   'x' => 298, 'y' => 158, 'airport' => ['SDJ', 'Sendai'],
            'prefectures' => ['Aomori', 'Iwate', 'Miyagi', 'Akita', 'Yamagata', 'Fukushima'], 'cities' => ['Sendai']],
        'kanto'    => ['name' => 'Kanto',    'jp' => '関東',   'x' => 278, 'y' => 230, 'airport' => ['NRT', 'Tokyo'],
            'prefectures' => ['Ibaraki', 'Tochigi', 'Gunma', 'Saitama', 'Chiba', 'Tokyo', 'Kanagawa'], 'cities' => ['Yokohama', 'Kawasaki', 'Chiba', 'Saitama']],
        'chubu'    => ['name' => 'Chubu',    'jp' => '中部',   'x' => 234, 'y' => 258, 'airport' => ['NGO', 'Nagoya'],
            'prefectures' => ['Niigata', 'Toyama', 'Ishikawa', 'Fukui', 'Yamanashi', 'Nagano', 'Gifu', 'Shizuoka', 'Aichi'], 'cities' => ['Nagoya', 'Hamamatsu', 'Toyota', 'Kanazawa']],
        'kansai'   => ['name' => 'Kansai',   'jp' => '関西',   'x' => 190, 'y' => 290, 'airport' => ['KIX', 'Osaka'],
            'prefectures' => ['Mie', 'Shiga', 'Kyoto', 'Osaka', 'Hyogo', 'Nara', 'Wakayama'], 'cities' => ['Kobe', 'Sakai', 'Himeji']],
        'chugoku'  => ['name' => 'Chugoku',  'jp' => '中国',   'x' => 128, 'y' => 316, 'airport' => ['HIJ', 'Hiroshima'],
            'prefectures' => ['Tottori', 'Shimane', 'Okayama', 'Hiroshima', 'Yamaguchi'], 'cities' => ['Kurashiki', 'Fukuyama']],
        'shikoku'  => ['name' => 'Shikoku',  'jp' => '四国',   'x' => 164, 'y' => 352, 'airport' => ['TAK', 'Takamatsu'],
            'prefectures' => ['Tokushima', 'Kagawa', 'Ehime', 'Kochi'], 'cities' => ['Takamatsu', 'Matsuyama']],
        'kyushu'   => ['name' => 'Kyushu & Okinawa', 'jp' => '九州・沖縄', 'x' => 70, 'y' => 374, 'airport' => ['FUK', 'Fukuoka'],
            'prefectures' => ['Fukuoka', 'Saga', 'Nagasaki', 'Kumamoto', 'Oita', 'Miyazaki', 'Kagoshima', 'Okinawa'], 'cities' => ['Kitakyushu', 'Naha']],
    ],

    /*
    | Rough JPY→IDR rate for the "show salary in Rupiah" toggle. It is only
    | an estimate and the UI labels it as such.
    */
    'jpy_to_idr' => (float) env('PASSION_JPY_TO_IDR', 105),

    /*
    | The separate learning platform (config/brand.php names it). Overridable
    | under Admin → Website Settings.
    */
    'academy' => [
        'url' => env('ACADEMY_URL'),
    ],
];
