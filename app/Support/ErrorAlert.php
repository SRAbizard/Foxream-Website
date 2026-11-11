<?php

namespace App\Support;

use App\Mail\AlertMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Emails ALERT_EMAIL when the live site hits an error, so it is known
 * before a candidate reports it. The same error is sent once an hour at
 * most, and no more than a set number a day. Only what helps find the
 * fault is sent: never the form data people typed.
 */
class ErrorAlert
{
    public static function send(Throwable $e): void
    {
        $to = config('ops.alert_email');
        if (blank($to) || ! config('ops.errors.enabled')) {
            return;
        }

        // A mail server that is down must not turn one error into two.
        rescue(function () use ($e, $to) {
            $signature = 'error-alert:'.md5($e::class.'|'.$e->getFile().'|'.$e->getLine());
            if (! Cache::add($signature, true, now()->addMinutes(config('ops.errors.repeat_minutes')))) {
                return;
            }

            $today = 'error-alert-count:'.now()->toDateString();
            Cache::add($today, 0, now()->endOfDay());
            if (Cache::increment($today) > config('ops.errors.per_day')) {
                return;
            }

            Mail::to($to)->send(new AlertMail(
                'Error: '.Str::limit(class_basename($e).' — '.$e->getMessage(), 120),
                self::details($e),
            ));
        }, report: false);
    }

    /** @return array<string, string|null> */
    public static function details(Throwable $e): array
    {
        // A web page being served has a route; a command or a queued job has none.
        $request = request()->route() ? request() : null;
        $base    = base_path().DIRECTORY_SEPARATOR;

        // The part of the trace that is our own code: where to start looking.
        $ours = collect($e->getTrace())
            ->filter(fn ($f) => isset($f['file']) && ! str_contains($f['file'], DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR))
            ->take(8)
            ->map(fn ($f) => str_replace($base, '', $f['file']).':'.($f['line'] ?? '?'))
            ->implode("\n");

        return [
            'When'    => now()->toDateTimeString().' ('.config('app.timezone').')',
            'Error'   => $e::class,
            'Message' => Str::limit($e->getMessage(), 1000),
            'Where'   => str_replace($base, '', $e->getFile()).':'.$e->getLine(),
            'Page'    => $request ? $request->method().' '.$request->url() : 'console: '.implode(' ', $_SERVER['argv'] ?? []),
            'User'    => $request?->user() ? '#'.$request->user()->getKey() : null,
            'Trace'   => $ours ?: null,
            'Log'     => 'Full details: storage/logs on the server',
        ];
    }
}
