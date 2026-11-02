<?php

namespace App\Models;

use App\Support\HasJsonTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tsk_id', 'slug', 'name', 'name_ja','logo_path', 'website', 'industry', 'country', 'city', 'description', 'is_verified', 'is_active'])]
class Company extends Model
{
    use HasJsonTranslations;

    public array $translatable = ['description'];

    protected function casts(): array
    {
        return [
            'description' => 'array',
            'is_verified' => 'boolean',
            'is_active'   => 'boolean',
        ];
    }

    /** The Japanese name on the Japanese site when there is one, else the name. */
    public function localName(): string
    {
        return app()->getLocale() === 'ja' && filled($this->name_ja) ? $this->name_ja : $this->name;
    }

    /** The TSK (登録支援機関) this company works with. */
    public function tsk(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tsk::class);
    }

    public function vacancies(): HasMany
    {
        return $this->hasMany(JobVacancy::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }
}
