<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/** A TSK (登録支援機関, registered support organisation) and the companies it supports. */
class Tsk extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Companies stay when their TSK is deleted (no foreign key on SQLite to do it). */
    protected static function booted(): void
    {
        static::deleting(fn (self $tsk) => $tsk->companies()->update(['tsk_id' => null]));
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function vacancies(): HasManyThrough
    {
        return $this->hasManyThrough(JobVacancy::class, Company::class);
    }
}
