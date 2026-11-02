<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'job_vacancy_id', 'status', 'cover_letter', 'admin_notes', 'reviewed_at', 'reviewed_by',
    'contract_path', 'signed_contract_path', 'signed_contract_at', 'coe_issued_at', 'coe_path',
    'visa_issued_at', 'visa_path', 'departure_at', 'flight_info', 'ticket_path',
])]
class Application extends Model
{
    /**
     * The steps of an application in order, from applying to departure.
     * The admin moves the application along; rejected and withdrawn end it.
     */
    public const STAGES = [
        'submitted',
        'screening_id',
        'screening_jp',
        'interview',
        'passed',
        'contract',
        'coe',
        'visa',
        'departure',
        'departed',
    ];

    public const STATUSES = [...self::STAGES, 'rejected', 'withdrawn'];

    public const STATUS_COLORS = [
        'submitted'    => 'gray',
        'screening_id' => 'info',
        'screening_jp' => 'info',
        'interview'    => 'warning',
        'passed'       => 'success',
        'contract'     => 'success',
        'coe'          => 'success',
        'visa'         => 'success',
        'departure'    => 'primary',
        'departed'     => 'primary',
        'rejected'     => 'danger',
        'withdrawn'    => 'gray',
    ];

    /** The schedule type that belongs to each step, for the admin's "add schedule". */
    public const STAGE_SCHEDULE = [
        'submitted'    => 'screening_id',
        'screening_id' => 'screening_id',
        'screening_jp' => 'screening_jp',
        'interview'    => 'interview',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at'        => 'datetime',
            'signed_contract_at' => 'datetime',
            'coe_issued_at'      => 'date',
            'visa_issued_at'     => 'date',
            'departure_at'       => 'datetime',
        ];
    }

    /** Public URL of one of the application's files (contract, COE, visa, ticket). */
    public function fileUrl(string $column): ?string
    {
        return $this->{$column} ? asset('storage/'.$this->{$column}) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class, 'job_vacancy_id');
    }

    /** Alias for vacancy() — easier-to-read in student-facing code. */
    public function jobVacancy(): BelongsTo
    {
        return $this->vacancy();
    }

    /** Screenings, interviews and kaiwa practice, the soonest first; dates still to come last. */
    public function schedules(): HasMany
    {
        return $this->hasMany(ApplicationSchedule::class)
            ->orderByRaw('starts_at IS NULL')->orderBy('starts_at');
    }

    protected static function booted(): void
    {
        // The application's files go with it (schedules cascade in the database).
        static::deleted(function (self $app) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete(array_filter([
                $app->contract_path, $app->signed_contract_path, $app->coe_path, $app->visa_path, $app->ticket_path,
            ]));
        });

        static::updated(function (self $app) {
            if (! $app->user) {
                return;
            }
            if ($app->wasChanged('status')) {
                $old = $app->getOriginal('status');
                $app->user->notify(new \App\Notifications\ApplicationStatusChanged($app, $old, $app->status));
            }

            $url = \App\Filament\Student\Pages\MyApplications::getUrl(panel: 'student');
            $job = $app->jobVacancy?->t('title');
            if ($app->wasChanged('contract_path') && $app->contract_path) {
                $app->user->notify(new \App\Notifications\ApplicationNews(
                    __('Your employment contract is ready'),
                    __('The employment contract for ":title" is ready. Download it, sign it and upload the signed copy.', ['title' => $job]),
                    $url,
                ));
            }
            if ($app->wasChanged(['departure_at', 'ticket_path']) && $app->departure_at) {
                $app->user->notify(new \App\Notifications\ApplicationNews(
                    __('Your departure is scheduled'),
                    __('Departure: :when. See the flight details and your ticket.', ['when' => $app->departure_at->translatedFormat('l, d M Y · H:i').' WIB']),
                    $url,
                    'heroicon-o-paper-airplane',
                ));
            }
        });
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereNotIn('status', ['rejected', 'withdrawn']);
    }

    public function getStatusLabelAttribute(): string
    {
        return __('application.status.'.$this->status);
    }

    /** The step after this one, or null at the end or when the application ended. */
    public function nextStage(): ?string
    {
        $i = array_search($this->status, self::STAGES, true);

        return $i === false ? null : (self::STAGES[$i + 1] ?? null);
    }

    /** Has the application reached (or passed) this step? */
    public function reached(string $stage): bool
    {
        $at = array_search($this->status, self::STAGES, true);

        return $at !== false && $at >= array_search($stage, self::STAGES, true);
    }
}
