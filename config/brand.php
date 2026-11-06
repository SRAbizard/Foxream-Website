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

    // The first-visit intro video (in public/): a landscape and a portrait cut.
    'intro' => [
        'desktop' => 'videos/intro-desktop.mp4',
        'mobile'  => 'videos/intro-mobile.mp4',
    ],
];
