<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    use HasFactory;

    public const VISA_TARGET_STATUSES = ['pending', 'confirmed', 'rejected', 'changed'];

    public const VISA_TARGET_STATUS_COLORS = [
        'pending'   => 'warning',
        'confirmed' => 'success',
        'rejected'  => 'danger',
        'changed'   => 'info',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'birthdate'                => 'date',
            'passport_expires_at'      => 'date',
            'smoker'                   => 'boolean',
            'drinker'                  => 'boolean',
            'visa_target_requested_at' => 'datetime',
            'visa_target_reviewed_at'  => 'datetime',
            'licenses'                 => 'array',
            'visa_request'             => 'array',
            'eats_pork'                => 'boolean',
            'has_tattoo'               => 'boolean',
            'has_debt'                 => 'boolean',
            'colour_blind'             => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function primaryVisa(): BelongsTo
    {
        return $this->belongsTo(VisaCategory::class, 'primary_visa_category_id');
    }

    public function internshipCampus(): BelongsTo
    {
        return $this->belongsTo(PartnerCampus::class, 'internship_campus_id');
    }

    /** The internship campus: a partner campus, or the name typed when it is not on the list. */
    public function campusName(): ?string
    {
        return $this->internshipCampus?->label ?? $this->internship_campus_other;
    }

    public function visaTargetReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'visa_target_reviewed_by');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }

    /**
     * Has the student's chosen visa been confirmed by an admin?
     */
    public function hasConfirmedVisa(): bool
    {
        return $this->visa_target_status === 'confirmed' && $this->primary_visa_category_id !== null;
    }

    /**
     * Compute profile completion percentage based on filled key fields.
     */
    public function completionPct(): int
    {
        $fields = [
            'full_name', 'gender', 'birthdate', 'birthplace', 'religion',
            'id_number', 'address', 'city', 'province',
            'emergency_contact_name', 'emergency_contact_phone',
        ];
        $filled = collect($fields)->filter(fn ($f) => filled($this->$f))->count();
        return (int) round(($filled / count($fields)) * 100);
    }
}
