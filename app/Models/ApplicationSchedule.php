<?php

namespace App\Models;

use App\Notifications\ScheduleUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A screening, job interview or kaiwa practice for an application: when,
 * the meeting link, notes for the student, and the result. Date and link
 * may follow later; the student is notified when they are set or change.
 */
class ApplicationSchedule extends Model
{
    public const TYPES = ['screening_id', 'screening_jp', 'interview', 'kaiwa'];

    public const RESULTS = ['passed', 'failed'];

    /** Japan time, shown next to WIB for meetings with the Japan side. */
    public const JAPAN_TZ = 'Asia/Tokyo';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return __('schedule.type.'.$this->type);
    }

    /** "Senin, 10 Nov 2026 · 09:00 WIB (11:00 JST)", or null while the date is to come. */
    public function getWhenAttribute(): ?string
    {
        if (! $this->starts_at) {
            return null;
        }
        $at = $this->starts_at->copy()->locale(app()->getLocale());

        return $at->translatedFormat('l, d M Y · H:i').' WIB ('.$at->copy()->setTimezone(self::JAPAN_TZ)->format('H:i').' JST)';
    }

    public function isUpcoming(): bool
    {
        return ! $this->result && (! $this->starts_at || $this->starts_at->gt(now()->subHours(3)));
    }

    protected static function booted(): void
    {
        // Tell the student about a new schedule, and when its date or link changes.
        static::created(fn (self $schedule) => $schedule->application?->user?->notify(new ScheduleUpdated($schedule, true)));
        static::updated(function (self $schedule) {
            if ($schedule->wasChanged(['starts_at', 'meeting_url'])) {
                $schedule->application?->user?->notify(new ScheduleUpdated($schedule, false));
            }
        });
    }
}
