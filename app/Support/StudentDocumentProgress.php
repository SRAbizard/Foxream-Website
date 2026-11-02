<?php

namespace App\Support;

use App\Models\User;
use App\Models\VisaCategory;

/**
 * Compute a student's document-completion progress against the required
 * document set defined per visa category.
 *
 * Required = union of required_documents across visa categories from the
 *            student's applications (deduplicated). Falls back to the
 *            "default" set if the student has no applications yet.
 */
class StudentDocumentProgress
{
    /**
     * The default required types when a student has no applications yet.
     * Common baseline that every visa needs.
     */
    public const DEFAULT_REQUIRED = ['ktp', 'passport', 'ijazah', 'photo'];

    /**
     * Visa category slugs the student is targeting.
     *
     * Resolution order:
     *   1. The student_profile.primary_visa_category_id IF status='confirmed'
     *      (admin-approved authoritative target)
     *   2. Otherwise, the union of visa categories from the student's job
     *      applications (best-effort inference)
     *
     * @return array<int, string>
     */
    public static function targetVisaSlugs(User $user): array
    {
        $user->loadMissing('studentProfile.primaryVisa');

        if ($user->studentProfile?->hasConfirmedVisa()) {
            return [$user->studentProfile->primaryVisa->slug];
        }

        return $user->applications()
            ->with('vacancy.visaCategory')
            ->get()
            ->pluck('vacancy.visaCategory.slug')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Required document type keys for this user.
     *
     * @return array<int, string>
     */
    public static function requiredTypes(User $user): array
    {
        return array_values(array_diff(static::allRequiredTypes($user), static::laterTypes($user)));
    }

    /**
     * The visa's required documents plus those of the student's field
     * (the job category picked on the profile), before any wait for the
     * contract.
     */
    private static function allRequiredTypes(User $user): array
    {
        $slugs = static::targetVisaSlugs($user);

        if (empty($slugs)) {
            return static::DEFAULT_REQUIRED;
        }

        $types = VisaCategory::whereIn('slug', $slugs)->get()
            ->flatMap(fn ($visa) => $visa->requiredDocumentTypes())
            ->merge(\App\Models\JobCategory::where('slug', $user->studentProfile?->desired_job)->first()?->required_documents ?? [])
            ->unique()
            ->values()
            ->all();

        return ! empty($types) ? $types : static::DEFAULT_REQUIRED;
    }

    /**
     * Required documents that can wait until the employment contract is
     * issued (e.g. the passport): empty once an application reached it.
     *
     * @return array<int, string>
     */
    public static function laterTypes(User $user): array
    {
        $contract = $user->applications()->active()->get()->contains(fn ($a) => $a->reached('contract'));
        if ($contract) {
            return [];
        }

        $later = \App\Models\DocumentType::where('after_contract', true)->pluck('key')->all();

        return array_values(array_intersect(static::allRequiredTypes($user), $later));
    }

    /**
     * Optional document type keys (encouraged but not blocking).
     * Excludes any key already present in the required set.
     *
     * @return array<int, string>
     */
    public static function optionalTypes(User $user): array
    {
        $slugs = static::targetVisaSlugs($user);
        if (empty($slugs)) return [];

        $required = static::requiredTypes($user);

        // Documents that wait for the contract can be uploaded early.
        return VisaCategory::whereIn('slug', $slugs)->get()
            ->flatMap(fn ($visa) => $visa->optionalDocumentTypes())
            ->merge(static::laterTypes($user))
            ->unique()
            ->reject(fn ($k) => in_array($k, $required, true))
            ->values()
            ->all();
    }

    /**
     * How was the target determined? Useful for UI labelling.
     * Returns one of: 'confirmed', 'applications', 'default'.
     */
    public static function targetSource(User $user): string
    {
        $user->loadMissing('studentProfile');
        if ($user->studentProfile?->hasConfirmedVisa()) {
            return 'confirmed';
        }
        if ($user->applications()->exists()) {
            return 'applications';
        }
        return 'default';
    }

    /**
     * Compute progress: required types vs uploaded vs verified.
     *
     * @return array{
     *     required: array<int, string>,
     *     required_count: int,
     *     uploaded_types: array<int, string>,
     *     uploaded_count: int,
     *     verified_types: array<int, string>,
     *     verified_count: int,
     *     missing_types: array<int, string>,
     *     missing_count: int,
     *     pct: int,
     *     using_default: bool,
     *     source: string,
     *     optional: array<int, string>,
     *     optional_count: int,
     *     optional_uploaded_types: array<int, string>,
     *     optional_uploaded_count: int,
     * }
     */
    public static function for(User $user): array
    {
        $required = static::requiredTypes($user);
        $optional = static::optionalTypes($user);
        $usingDefault = empty(static::targetVisaSlugs($user));

        $docs = $user->studentDocuments()->get();

        $uploadedTypes = $docs->whereIn('type', $required)
            ->pluck('type')->unique()->values()->all();

        $verifiedTypes = $docs->where('status', 'verified')
            ->whereIn('type', $required)
            ->pluck('type')->unique()->values()->all();

        $missingTypes = array_values(array_diff($required, $uploadedTypes));

        $optionalUploaded = $docs->whereIn('type', $optional)
            ->pluck('type')->unique()->values()->all();

        $pct = count($required) > 0
            ? (int) round((count($verifiedTypes) / count($required)) * 100)
            : 0;

        return [
            'required'                => $required,
            'required_count'          => count($required),
            'uploaded_types'          => $uploadedTypes,
            'uploaded_count'          => count($uploadedTypes),
            'verified_types'          => $verifiedTypes,
            'verified_count'          => count($verifiedTypes),
            'missing_types'           => $missingTypes,
            'missing_count'           => count($missingTypes),
            'pct'                     => $pct,
            'using_default'           => $usingDefault,
            'source'                  => static::targetSource($user),
            'optional'                => $optional,
            'optional_count'          => count($optional),
            'optional_uploaded_types' => $optionalUploaded,
            'optional_uploaded_count' => count($optionalUploaded),
            'later'                   => static::laterTypes($user),
        ];
    }
}
