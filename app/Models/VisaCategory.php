<?php

namespace App\Models;

use App\Support\HasJsonTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'description', 'color', 'sort_order', 'required_documents', 'apply_documents', 'optional_documents'])]
class VisaCategory extends Model
{
    use HasJsonTranslations;

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'name'               => 'array',
            'description'        => 'array',
            'required_documents' => 'array',
            'apply_documents'    => 'array',
            'optional_documents' => 'array',
        ];
    }

    public function vacancies(): HasMany
    {
        return $this->hasMany(JobVacancy::class);
    }

    public function workflowSteps(): HasMany
    {
        return $this->hasMany(VisaWorkflowStep::class)->orderBy('sort_order');
    }

    /**
     * Required document type keys (subset of DocumentType keys).
     * Required = student MUST upload, counts in progress %.
     */
    public function requiredDocumentTypes(): array
    {
        return array_values(array_filter((array) ($this->required_documents ?? [])));
    }

    /**
     * The required documents a student needs to apply (photo, certificates…);
     * the other required ones can wait until they pass the company interview.
     */
    public function applyDocumentTypes(): array
    {
        return array_values(array_intersect((array) ($this->apply_documents ?? []), $this->requiredDocumentTypes()));
    }

    /**
     * Optional document type keys.
     * Optional = student MAY upload, accepted but does not affect progress %.
     */
    public function optionalDocumentTypes(): array
    {
        return array_values(array_filter((array) ($this->optional_documents ?? [])));
    }
}
