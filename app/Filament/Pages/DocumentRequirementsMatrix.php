<?php

namespace App\Filament\Pages;

use App\Models\DocumentType;
use App\Models\VisaCategory;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

class DocumentRequirementsMatrix extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-table-cells';
    protected static string | UnitEnum | null $navigationGroup = 'Students';
    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.document-requirements-matrix';

    /**
     * $matrix[<visa_slug>][<doc_type_key>] = 'apply' | 'required' | 'optional' | null/missing
     *   apply    = required, and needed to apply (photo, certificates…)
     *   required = required once the student passes the company interview
     */
    public array $matrix = [];

    public static function getNavigationLabel(): string { return __('Document Requirements Matrix'); }
    public static function getNavigationGroup(): ?string { return __('Students'); }
    public function getTitle(): string                 { return __('Document Requirements Matrix'); }

    public function mount(): void
    {
        $this->loadMatrix();
    }

    public function loadMatrix(): void
    {
        // Only load keys that map to a *currently active* document type.
        // Anything else is an orphan (admin deleted / deactivated the type)
        // and would otherwise inflate the per-visa counters.
        $activeKeys = DocumentType::active()->pluck('key')->all();
        $activeSet  = array_flip($activeKeys); // O(1) lookup

        $this->matrix = VisaCategory::orderBy('sort_order')->get()
            ->mapWithKeys(function ($v) use ($activeSet) {
                $cell = [];
                foreach ($v->applyDocumentTypes() as $key) {
                    if (isset($activeSet[$key])) {
                        $cell[$key] = 'apply';
                    }
                }
                foreach ($v->requiredDocumentTypes() as $key) {
                    if (isset($cell[$key])) {
                        continue;
                    }
                    if (isset($activeSet[$key])) {
                        $cell[$key] = 'required';
                    }
                }
                foreach ($v->optionalDocumentTypes() as $key) {
                    if (isset($activeSet[$key]) && ! isset($cell[$key])) {
                        $cell[$key] = 'optional';
                    }
                }
                return [$v->slug => $cell];
            })->all();
    }

    public function getVisas()
    {
        return VisaCategory::orderBy('sort_order')->get();
    }

    public function getDocumentTypes()
    {
        return DocumentType::active()->ordered()->get();
    }

    /**
     * Cycle a cell: none → to apply → after passing → optional → none.
     */
    public function cycle(string $visaSlug, string $typeKey): void
    {
        $current = $this->matrix[$visaSlug][$typeKey] ?? null;
        $next = match ($current) {
            null       => 'apply',
            'apply'    => 'required',
            'required' => 'optional',
            'optional' => null,
            default    => 'apply',
        };

        if ($next === null) {
            unset($this->matrix[$visaSlug][$typeKey]);
        } else {
            $this->matrix[$visaSlug][$typeKey] = $next;
        }
    }

    public function setRequiredAllForVisa(string $visaSlug): void
    {
        $this->matrix[$visaSlug] = $this->getDocumentTypes()
            ->mapWithKeys(fn ($t) => [$t->key => 'required'])
            ->all();
    }

    public function clearVisa(string $visaSlug): void
    {
        $this->matrix[$visaSlug] = [];
    }

    public function save(): void
    {
        // Defensive: only persist keys that still map to an active document
        // type. Anything else gets pruned from the JSON columns.
        $activeKeys = DocumentType::active()->pluck('key')->all();
        $activeSet  = array_flip($activeKeys);

        foreach ($this->matrix as $visaSlug => $cells) {
            $apply = [];
            $required = [];
            $optional = [];
            foreach ($cells as $key => $state) {
                if (! isset($activeSet[$key])) continue;
                if ($state === 'apply') $apply[] = $key;
                if (in_array($state, ['apply', 'required'], true)) $required[] = $key;
                if ($state === 'optional') $optional[] = $key;
            }
            VisaCategory::where('slug', $visaSlug)->update([
                'apply_documents'    => array_values(array_unique($apply)),
                'required_documents' => array_values(array_unique($required)),
                'optional_documents' => array_values(array_unique($optional)),
            ]);
        }

        Notification::make()
            ->title(__('Requirements updated'))
            ->body(__('Required documents per visa have been saved.'))
            ->success()
            ->send();

        $this->loadMatrix();
    }

    /**
     * Helpers for the view to count states per visa.
     *
     * @return array{apply: int, required: int, optional: int}
     */
    public function countsFor(string $visaSlug): array
    {
        $cells = $this->matrix[$visaSlug] ?? [];
        return [
            'apply'    => count(array_filter($cells, fn ($s) => $s === 'apply')),
            'required' => count(array_filter($cells, fn ($s) => $s === 'required')),
            'optional' => count(array_filter($cells, fn ($s) => $s === 'optional')),
        ];
    }
}
