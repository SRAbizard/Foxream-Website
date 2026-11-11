<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** A plain notice for whoever runs the site: an error, a failed backup. */
class AlertMail extends Mailable
{
    /** @param array<string, string|null> $lines label => value, shown in this order */
    public function __construct(public string $title, public array $lines)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '['.config('app.name').'] '.$this->title);
    }

    public function content(): Content
    {
        $rows = collect($this->lines)
            ->filter(fn ($v) => filled($v))
            ->map(fn ($v, $k) => '<tr><th align="left" valign="top" style="padding:4px 12px 4px 0">'.e($k).'</th><td style="padding:4px 0;font-family:monospace;white-space:pre-wrap">'.e($v).'</td></tr>')
            ->implode('');

        return new Content(htmlString: '<h2 style="font-family:sans-serif">'.e($this->title).'</h2><table style="font-family:sans-serif;font-size:14px">'.$rows.'</table>');
    }
}
