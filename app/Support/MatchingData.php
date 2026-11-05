<?php

namespace App\Support;

use App\Models\JobCategory;
use App\Models\JobVacancy;
use App\Models\VisaCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Data behind the interactive job-matching tools (hero finder, match quiz,
 * region map, featured cards), shared by the homepage and /jobs. Everything
 * is serialised into the page once as JSON (#pj-home-data) and driven by
 * resources/js/home.js.
 */
class MatchingData
{
    private ?Collection $jobs = null;
    private ?Collection $visas = null;

    /** Published vacancies as plain arrays (newest / featured first). */
    public function jobs(): Collection
    {
        return $this->jobs ??= JobVacancy::published()
            ->with('company', 'jobCategory', 'visaCategory')
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->limit(300)
            ->get()
            ->map(fn (JobVacancy $v) => [
                'title'        => $v->t('title'),
                'company'      => $v->company?->name,
                'location'     => $v->location_display,
                'region'       => JapanRegions::keyFor($v->location_prefecture, $v->location_city),
                'category'     => $v->jobCategory?->slug,
                'categoryName' => $v->jobCategory?->t('name'),
                'visa'         => $v->visaCategory?->slug,
                'visaName'     => $v->visaCategory?->t('name'),
                'min'          => $v->salary_min,
                'max'          => $v->salary_max,
                'currency'     => $v->salary_currency,
                'period'       => $v->salary_period,
                'featured'     => (bool) $v->is_featured,
                'new'          => $v->published_at?->gt(now()->subDays(14)) ?? false,
                'url'          => route('job.show', $v->slug),
            ])->values();
    }

    public function regions(): Collection
    {
        $jobs = $this->jobs();

        return collect(JapanRegions::all())->map(fn ($r, $key) => [
            'key'     => $key,
            'name'    => __($r['name']),
            'jp'      => $r['jp'],
            'x'       => $r['x'],
            'y'       => $r['y'],
            'airport' => $r['airport'],
            'count'   => $jobs->where('region', $key)->count(),
        ])->values();
    }

    public function visas(): Collection
    {
        return $this->visas ??= VisaCategory::orderBy('sort_order')->get();
    }

    public function categories(): Collection
    {
        return JobCategory::orderBy('sort_order')->get();
    }

    /** The three quiz routes, resolved to this install's visa slugs. */
    public function visaRoutes(): array
    {
        $slug = fn (array $needles) => $this->visas()
            ->first(fn ($v) => Str::contains(Str::lower($v->slug.' '.$v->t('name', 'en')), $needles))?->slug;

        return [
            'ssw'      => ['slug' => $slug(['tokutei', 'ssw', 'specified']), 'name' => 'Tokutei Ginou (SSW)'],
            'intern'   => ['slug' => $slug(['intern', 'magang', 'jisshu', 'trainee']), 'name' => __('Internship (Ginou Jisshu)')],
            'engineer' => ['slug' => $slug(['engineer', 'gijinkoku']), 'name' => __('Engineer (Gijinkoku)')],
        ];
    }

    public static function rate(): float
    {
        return (float) config('site.jpy_to_idr', 105);
    }

    public function quiz(): array
    {
        return [
            ['q' => __('How old are you?'), 'k' => 'age', 'o' => [
                [__('18–22 years'), __('Fresh graduate'), 0], [__('23–27 years'), '', 1], [__('28–32 years'), '', 2], [__('33 or older'), '', 3]]],
            ['q' => __('What is your Japanese level?'), 'k' => 'lang', 'o' => [
                [__('None yet'), __('Starting from zero'), 0], [__('N5 / beginner'), __('Hiragana & katakana'), 1], [__('N4 / JFT A2'), __('Basic conversation'), 2], [__('N3 or higher'), __('Fluent'), 3]]],
            ['q' => __('Which field interests you?'), 'k' => 'field', 'o' => [
                [__('Caregiving'), 'Kaigo', ['kaigo']],
                [__('Driver'), __('Truck, taxi, bus'), ['driver-truck', 'driver-taksi', 'driver-bus']],
                [__('Factory & food'), __('Manufacturing, food processing'), ['manufaktur', 'pengolahan-makanan']],
                [__('Construction & automotive'), __('Construction, vehicle maintenance'), ['konstruksi', 'otomotif']],
                [__('Hotel & cleaning'), __('Hospitality, building cleaning'), ['perhotelan', 'building-cleaning']],
                [__('Farming & livestock'), __('Agriculture, livestock'), ['pertanian', 'peternakan']]]],
            ['q' => __('Your highest education?'), 'k' => 'edu', 'o' => [
                [__('High school / vocational'), 'SMA / SMK', 0], [__('Diploma (D3)'), '', 1], [__('Bachelor in engineering / IT'), '', 2], [__('Other bachelor'), '', 3]]],
        ];
    }

    /** The JSON payload read by resources/js/home.js. */
    public function payload(): array
    {
        return [
            'jobs'    => $this->jobs(),
            'regions' => $this->regions(),
            'rate'    => self::rate(),
            'jobsUrl' => route('job.index'),
            'academy' => SiteSettings::academyUrl(),
            'routes'  => $this->visaRoutes(),
            'quiz'    => $this->quiz(),
            'i18n'    => [
                'allFields'    => __('All fields'),
                'jobsInRegion' => __('jobs in this region'),
                'noJobsRegion' => __('No openings here yet. New jobs come in every week.'),
                'seeRegion'    => __('See all in :region', ['region' => ':region']),
                'question'     => __('QUESTION'),
                'done'         => __('MISSION COMPLETE'),
                'bestRoute'    => __('BEST ROUTE FOR YOU'),
                'seeJobs'      => __('See these jobs'),
                'again'        => __('Try again'),
                'academyNote'  => __('Raise your Japanese to N4 first at :learning, then come back here to apply.'),
                'academyLink'  => __('Open :learning'),
                'noMatch'      => __('No matching openings right now. We will tell you as soon as one opens.'),
                'whyEngineer'  => __('Your engineering/IT education opens the Engineer visa route.'),
                'whySsw'       => __('Your Japanese level already meets the Tokutei Ginou requirement.'),
                'whyIntern'    => __('The internship route lets you start from the basics; you are trained before departure.'),
                'whySswPrep'   => __('Get your Japanese to N4 so you can apply through Tokutei Ginou.'),
                'perMonth'     => __('/ month'),
                'estimate'     => __('estimated rate'),
                'mil'          => __('mil'),
            ],
        ];
    }

    /** JSON for the <script id="pj-home-data"> tag (safe to print raw). */
    public function json(): string
    {
        return json_encode($this->payload(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    }
}
