<?php

namespace App\Support;

use App\Models\StudentDocument;
use App\Models\StudentProfile;

/**
 * What a student must hand in to request each visa target, before an
 * admin approves it:
 *   - Tokutei Ginou: JFT A2 / JLPT N4 or higher, and an SSW certificate
 *   - Gijinkoku:     JLPT N3 or higher, the diploma and the transcript
 *   - Internship:    the campus (not every campus works with us)
 * Keyed by visa category slug. The uploads are kept as student documents
 * and listed in student_profiles.visa_request.
 */
class VisaRequirements
{
    /** Language certificates (Certificates::GROUPS['language'] keys) accepted per visa. */
    public const LANGUAGE = [
        'tokutei-ginou' => ['jft_a2', 'jlpt_n4', 'jlpt_n3', 'jlpt_n2', 'jlpt_n1'],
        'engineering'   => ['jlpt_n3', 'jlpt_n2', 'jlpt_n1'],
    ];

    /** Request part => document type the upload is stored as. */
    public const DOCUMENTS = [
        'language'   => 'jlpt_jft',
        'ssw'        => 'ssw',
        'ijazah'     => 'ijazah',
        'transcript' => 'transcript',
    ];

    public static function language(?string $slug): array
    {
        return self::LANGUAGE[$slug] ?? [];
    }

    public static function needsSsw(?string $slug): bool
    {
        return $slug === 'tokutei-ginou';
    }

    public static function needsDiploma(?string $slug): bool
    {
        return $slug === 'engineering';
    }

    public static function needsCampus(?string $slug): bool
    {
        return $slug === 'internship';
    }

    /** The parts of a request that come with an upload, for this visa. */
    public static function parts(?string $slug): array
    {
        return array_values(array_filter([
            self::language($slug) ? 'language' : null,
            self::needsSsw($slug) ? 'ssw' : null,
            self::needsDiploma($slug) ? 'ijazah' : null,
            self::needsDiploma($slug) ? 'transcript' : null,
        ]));
    }

    /** Ids of the documents sent with the request for the current visa target. */
    public static function documentIds(StudentProfile $profile): array
    {
        $parts = array_flip(self::parts($profile->primaryVisa?->slug));

        return collect(array_intersect_key($profile->visa_request ?? [], $parts))->pluck('document_id')->filter()->values()->all();
    }

    /**
     * What the student handed in, for the admin: one line per part with
     * a label, a value (campus or certificate), the uploaded document and
     * a warning when the campus is not a partner.
     *
     * @return array<int, array{label: string, value: ?string, document: ?StudentDocument, warning: ?string}>
     */
    public static function summary(StudentProfile $profile): array
    {
        $slug    = $profile->primaryVisa?->slug;
        $request = array_intersect_key($profile->visa_request ?? [], array_flip(self::parts($slug)));
        $docs    = StudentDocument::whereIn('id', collect($request)->pluck('document_id')->filter())->get()->keyBy('id');
        $lines   = [];

        if (self::needsCampus($slug)) {
            $lines[] = [
                'label'    => __('Campus'),
                'value'    => $profile->campusName() ?? '—',
                'document' => null,
                'warning'  => ! $profile->internship_campus_id && $profile->internship_campus_other ? __('Not on the partner campus list') : null,
            ];
        }

        $labels = ['language' => __('Language certificate'), 'ssw' => __('SSW certificate'), 'ijazah' => __('Diploma'), 'transcript' => __('Transcript')];
        foreach ($labels as $part => $label) {
            if (! isset($request[$part])) {
                continue;
            }
            $item  = $request[$part];
            $value = isset($item['type']) ? Certificates::label($item['type']) : null;
            if ($value && ! empty($item['year'])) {
                $value .= ' · '.$item['year'].(! empty($item['month']) ? '/'.$item['month'] : '');
            }
            $lines[] = ['label' => $label, 'value' => $value, 'document' => $docs->get($item['document_id'] ?? 0), 'warning' => null];
        }

        return $lines;
    }
}
