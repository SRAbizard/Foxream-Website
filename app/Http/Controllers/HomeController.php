<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\Testimonial;
use App\Support\HomepageDemoData;
use App\Support\MatchingData;
use App\Support\SiteSettings;
use Illuminate\View\View;

/**
 * Homepage — the company profile front door: hero with the job finder,
 * who we are, how the mission works, featured jobs, partners, gallery,
 * testimonials and FAQ. The heavier matching tools (quiz, region map)
 * live on /jobs; both pages share App\Support\MatchingData.
 */
class HomeController extends Controller
{
    public function index(MatchingData $matching): View
    {
        $faqs = Faq::published()->get();

        return view('welcome', [
            'matching'     => $matching,
            'jobs'         => $matching->jobs(),
            'regions'      => $matching->regions(),
            'categories'   => $matching->categories(),
            'visas'        => $matching->visas(),
            'workflows'    => HomepageDemoData::visaWorkflows(),
            'faqs'         => $faqs->map(fn ($f) => ['q' => $f->t('question'), 'a' => $f->t('answer')])->all(),
            'testimonials' => Testimonial::published()->get(),
            'partners'     => Company::query()->where('is_active', true)->orderBy('name')->get(),
            'albums'       => Gallery::published()
                ->withCount(['publishedItems as items_count'])
                ->whereHas('publishedItems')
                ->orderBy('sort_order')->orderByDesc('id')
                ->limit(8)->get(),
            'heroSlides'   => SiteSettings::heroSlides(),
            'companyVideo' => SiteSettings::companyVideo(),
            'academyUrl'   => SiteSettings::academyUrl(),
            // A stat left empty in Website Settings is not shown.
            'stats'        => array_values(array_filter([
                ['value' => SiteSettings::stat('students'),  'label' => __('students trained')],
                ['value' => SiteSettings::stat('workers'),   'label' => __('workers placed in Japan')],
                ['value' => SiteSettings::stat('companies'), 'label' => __('partner companies in Japan')],
            ], fn ($s) => filled($s['value']))),
        ]);
    }
}
