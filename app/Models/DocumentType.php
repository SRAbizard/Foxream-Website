<?php

namespace App\Models;

use App\Support\HasJsonTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DocumentType extends Model
{
    use HasFactory, HasJsonTranslations;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public array $translatable = ['label', 'description'];

    protected function casts(): array
    {
        return [
            'label'       => 'array',
            'description' => 'array',
            'is_active'   => 'boolean',
            'after_contract' => 'boolean',
        ];
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Map of key => translated label for the current locale, suitable for
     * Filament Select::options(). Pass `$activeOnly = true` to skip retired
     * types (the default — admins can re-enable in DocumentType admin).
     */
    public static function options(bool $activeOnly = true): array
    {
        return static::query()
            ->when($activeOnly, fn ($q) => $q->active())
            ->ordered()
            ->get()
            ->mapWithKeys(fn ($t) => [$t->key => $t->t('label')])
            ->all();
    }

    /**
     * Localised label for a type key. Falls back to the lang key
     * (`document.type.<key>`) so legacy code keeps working when the row
     * is missing — and ultimately to the raw key.
     */
    public static function labelFor(string $key): string
    {
        $row = static::where('key', $key)->first();
        if ($row) {
            return $row->t('label');
        }
        $fallback = __('document.type.'.$key);
        return $fallback === 'document.type.'.$key ? $key : $fallback;
    }

    /**
     * Bulk lookup: array<key,label> for a list of keys, in the order
     * given. Useful for the requirements matrix.
     *
     * @param  array<int, string>  $keys
     */
    public static function labelsFor(array $keys): Collection
    {
        $rows = static::whereIn('key', $keys)->get()->keyBy('key');
        return collect($keys)->mapWithKeys(fn ($k) => [$k => $rows->get($k)?->t('label') ?? static::labelFor($k)]);
    }
}
