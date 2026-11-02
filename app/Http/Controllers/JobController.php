<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\JobCategory;
use App\Models\JobVacancy;
use App\Models\VisaCategory;
use App\Support\JapanRegions;
use App\Support\MatchingData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobController extends Controller
{
    public function index(Request $request, MatchingData $matching): View
    {
        $q = JobVacancy::query()
            ->published()
            ->with('company', 'jobCategory', 'visaCategory')
            ->when($request->string('visa')->isNotEmpty(),
                fn ($q, $v) => $q->whereHas('visaCategory', fn ($q) => $q->where('slug', $request->string('visa'))))
            ->when($request->string('category')->isNotEmpty(),
                fn ($q, $c) => $q->whereHas('jobCategory', fn ($q) => $q->where('slug', $request->string('category'))))
            ->when($request->string('city')->isNotEmpty(),
                fn ($q, $c) => $q->where('location_city', 'like', '%'.$request->string('city').'%'))
            ->when(JapanRegions::get($request->string('region')->toString()),
                fn ($q) => JapanRegions::scope($q, $request->string('region')->toString()))
            ->when($request->string('q')->isNotEmpty(), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('title->id', 'like', $term)
                      ->orWhere('title->en', 'like', $term)
                      ->orWhere('title->ja', 'like', $term);
                });
            });

        return view('pages.job.index', [
            'vacancies' => $q->orderByDesc('is_featured')->orderByDesc('published_at')->paginate(9)->withQueryString(),
            'visas'     => VisaCategory::orderBy('sort_order')->withCount('vacancies')->get(),
            'jobCats'   => JobCategory::orderBy('sort_order')->withCount('vacancies')->get(),
            'regions'   => JapanRegions::all(),
            'matching'  => $matching,
        ]);
    }

    public function show(string $slug, Request $request): View
    {
        $vacancy = JobVacancy::published()
            ->where('slug', $slug)
            ->with('company', 'jobCategory', 'visaCategory')
            ->firstOrFail();

        $hasApplied = false;
        $blocker = null;
        if ($user = $request->user()) {
            $hasApplied = Application::where('user_id', $user->id)
                ->where('job_vacancy_id', $vacancy->id)
                ->exists();
            if (! $hasApplied && $user->hasRole('student')) {
                $blocker = \App\Support\JobMatching::blocker($user, $vacancy);
            }
        }

        $related = JobVacancy::published()
            ->where('id', '!=', $vacancy->id)
            ->when($vacancy->visa_category_id,
                fn ($q, $vid) => $q->where('visa_category_id', $vid))
            ->limit(3)
            ->get();

        return view('pages.job.show', compact('vacancy', 'hasApplied', 'blocker', 'related'));
    }

    public function apply(string $slug, Request $request): RedirectResponse
    {
        $vacancy = JobVacancy::published()->where('slug', $slug)->firstOrFail();

        if (! $request->user()) {
            return redirect()->route('filament.student.auth.login')
                ->with('status', __('Please log in first to apply for this position.'));
        }

        // Only users with the 'student' role can submit applications.
        // Admins/superadmins viewing the page get a friendly redirect.
        if (! $request->user()->hasRole('student')) {
            return redirect()->route('job.show', $slug)
                ->with('status', __('Only student accounts can apply. You are signed in as an admin.'));
        }

        $request->validate([
            'cover_letter' => ['nullable', 'string', 'max:3000'],
        ]);

        // Visa target approved, the job's visa, and a complete CV.
        if ($blocker = \App\Support\JobMatching::blocker($request->user(), $vacancy)) {
            return redirect()->route('job.show', $slug)->with('status', $blocker['message']);
        }

        $application = Application::firstOrCreate(
            ['user_id' => $request->user()->id, 'job_vacancy_id' => $vacancy->id],
            [
                'status'       => 'submitted',
                'cover_letter' => $request->string('cover_letter')->toString() ?: null,
            ],
        );

        $msg = $application->wasRecentlyCreated
            ? __('Application submitted! We will reach out within a few days.')
            : __('You have already applied for this position.');

        return redirect()->route('job.show', $slug)->with('status', $msg);
    }
}
