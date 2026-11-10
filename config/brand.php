<?php

/*
|--------------------------------------------------------------------------
| The brand
|--------------------------------------------------------------------------
| The name the site goes by, everywhere: titles, panels, footer, PDFs, the
| CV and the candidate lists. Translations write it as :brand (the name),
| :company (the legal name, for payments and the copyright) and :learning
| (the Japanese-learning platform). The logos are in public/images/brand,
| the intro videos in public/videos.
*/
return [
    'name'     => env('BRAND_NAME', 'Foxream'),
    'company'  => env('BRAND_COMPANY', 'Foxream'),
    'learning' => env('BRAND_LEARNING', 'Foxream Learning'),

    // Who built the site: "Made by …" in the footer. It stays when the
    // brand above changes; set BRAND_MAKER empty to drop the line.
    'maker'    => env('BRAND_MAKER', 'Foxream'),

    // The first-visit intro video (in public/): a landscape and a portrait cut.
    'intro' => [
        'desktop' => 'videos/intro-desktop.mp4',
        'mobile'  => 'videos/intro-mobile.mp4',
    ],
];
