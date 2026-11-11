<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    use HasFactory;

    public const TYPES = [
        'ktp', 'kk', 'passport', 'ijazah', 'transcript',
        'cv', 'photo', 'medical_check', 'sktm', 'sktm_polri',
        'jlpt_certificate', 'skill_certificate', 'other',
    ];

    public const STATUSES = ['pending', 'verified', 'rejected'];

    public const STATUS_COLORS = [
        'pending'  => 'warning',
        'verified' => 'success',
        'rejected' => 'danger',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getFileUrlAttribute(): ?string
    {
        // Through FileController: the owner and student managers only.
        return $this->file_path ? route('files.document', $this) : null;
    }

    protected static function booted(): void
    {
        static::updated(function (self $doc) {
            // Only notify when status flips to verified or rejected (not on every edit).
            if ($doc->wasChanged('status') && in_array($doc->status, ['verified', 'rejected'], true) && $doc->user) {
                $doc->user->notify(new \App\Notifications\DocumentVerified($doc));
            }
        });
    }
}
