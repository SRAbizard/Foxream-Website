<?php

/*
|--------------------------------------------------------------------------
| Running the live site: alerts and backups
|--------------------------------------------------------------------------
| ALERT_EMAIL gets an email when the site hits an error and when a backup
| fails. Backups run every night (php artisan backup:run, scheduled in
| routes/console.php; the server needs the Laravel cron line, see README).
*/
return [

    // Who is told when something breaks. Empty = no emails (errors are still logged).
    'alert_email' => env('ALERT_EMAIL'),

    'errors' => [
        // Emails about errors: on the live site only, unless switched on here.
        'enabled'        => (bool) env('ERROR_ALERTS', env('APP_ENV') === 'production'),
        // The same error is mailed again only after this long.
        'repeat_minutes' => (int) env('ERROR_ALERTS_REPEAT_MINUTES', 60),
        // Never more than this many error emails a day.
        'per_day'        => (int) env('ERROR_ALERTS_PER_DAY', 30),
    ],

    'backup' => [
        // A disk from config/filesystems.php. "backups" = storage/app/backups
        // on this server; point it at another server's disk for a copy that
        // survives the server itself.
        'disk'      => env('BACKUP_DISK', 'backups'),
        // Backups older than this many days are deleted.
        'keep_days' => (int) env('BACKUP_KEEP_DAYS', 14),
        // The zip is encrypted (AES-256) with this password. It holds the
        // candidates' passports and documents: always set one on the live site.
        'password'  => env('BACKUP_PASSWORD'),
        // When the nightly backup runs (server time).
        'at'        => env('BACKUP_AT', '02:00'),
    ],
];
