<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\SiteSettings;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\JobVacancies\JobVacancyResource;
use App\Filament\Resources\StudentDocuments\StudentDocumentResource;
use App\Filament\Resources\Students\StudentResource;
use App\Models\Application;
use App\Models\ContactMessage;
use App\Models\DocumentType;
use App\Models\JobVacancy;
use App\Models\StudentDocument;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\JapanRegions;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Admin dashboard — "管制室 · Mission Control". One full-width Livewire
 * widget: greeting + quick actions, KPI cards with 14-day sparklines,
 * the application pipeline, an activity chart (7/30/90 days), a job map
 * by region, a to-do list and a recent-activity feed. Client-side
 * behaviour (count-up, tooltips, map selection) is Alpine in the view.
 */
class AdminWelcomeWidget extends Widget
{
    protected string $view = 'filament.widgets.mission-control';
    protected int|string|array $columnSpan = 'full';

    // Render with the page: it is the whole dashboard, so a lazy
    // placeholder would just flash an empty card first.
    protected static bool $isLazy = false;

    /** Activity chart range in days. */
    public int $range = 30;

    public function setRange(int $days): void
    {
        $this->range = in_array($days, [7, 30, 90], true) ? $days : 30;
    }

    protected function getViewData(): array
    {
        $now = now('Asia/Jakarta');
        $students = fn () => User::role('student');

        $pending = [
            'apps'     => Application::whereIn('status', ['submitted', 'under_review'])->count(),
            'newApps'  => Application::where('status', 'submitted')->count(),
            'docs'     => StudentDocument::where('status', 'pending')->count(),
            'messages' => ContactMessage::whereNull('read_at')->count(),
            'visa'     => StudentProfile::whereIn('visa_target_status', ['pending', 'changed'])->count(),
        ];

        $kpis = [
            $this->kpi(__('Registered candidates'), $students()->count(), $students(), 'heroicon-o-user-group', 'sakura', StudentResource::getUrl('index')),
            $this->kpi(__('Applications to review'), $pending['apps'], Application::query(), 'heroicon-o-paper-airplane', 'red', ApplicationResource::getUrl('index')),
            $this->kpi(__('Documents to verify'), $pending['docs'], StudentDocument::query(), 'heroicon-o-document-check', 'kin',
                StudentDocumentResource::getUrl('index', ['filters' => ['status' => ['value' => 'pending']]])),
            $this->kpi(__('Unread messages'), $pending['messages'], ContactMessage::query(), 'heroicon-o-envelope', 'ai', ContactMessageResource::getUrl('index')),
            $this->kpi(__('Open job postings'), JobVacancy::published()->count(), JobVacancy::query(), 'heroicon-o-briefcase', 'matcha', JobVacancyResource::getUrl('index')),
            $this->kpi(__('Interviews scheduled'), Application::where('status', 'interview_scheduled')->count(), null, 'heroicon-o-calendar-days', 'washi',
                ApplicationResource::getUrl('index', ['filters' => ['status' => ['value' => 'interview_scheduled']]])),
        ];

        // Pipeline — the happy path, plus the exits.
        $counts = Application::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $stageUrl = fn (string $s) => ApplicationResource::getUrl('index', ['filters' => ['status' => ['value' => $s]]]);
        $pipeline = collect(['submitted', 'under_review', 'interview_scheduled', 'offered', 'accepted'])
            ->map(fn ($s) => ['status' => $s, 'label' => __('application.status.'.$s), 'count' => (int) ($counts[$s] ?? 0), 'url' => $stageUrl($s)]);
        $exits = collect(['rejected', 'withdrawn'])
            ->map(fn ($s) => ['status' => $s, 'label' => __('application.status.'.$s), 'count' => (int) ($counts[$s] ?? 0), 'url' => $stageUrl($s)]);

        // Activity chart.
        $days = $this->range;
        $appSeries = $this->daily(Application::query(), $days);
        $regSeries = $this->daily($students(), $days);
        $chart = collect(array_keys($appSeries))->map(fn ($d) => [
            'date'  => Carbon::parse($d)->translatedFormat($days > 30 ? 'd M' : 'D, d M'),
            'apps'  => $appSeries[$d],
            'regs'  => $regSeries[$d],
        ])->values();

        // Job map.
        $regions = collect(JapanRegions::all());
        $jobsByRegion = JobVacancy::published()->with('company')->get()
            ->groupBy(fn ($v) => JapanRegions::keyFor($v->location_prefecture, $v->location_city) ?? 'other');
        $map = $regions->map(fn ($r, $key) => [
            'key'   => $key,
            'name'  => __($r['name']),
            'jp'    => $r['jp'],
            'x'     => $r['x'],
            'y'     => $r['y'],
            'jobs'  => ($jobsByRegion[$key] ?? collect())->map(fn ($v) => [
                'title'   => $v->t('title'),
                'company' => $v->company?->name,
                'city'    => $v->location_display,
                'url'     => JobVacancyResource::getUrl('edit', ['record' => $v]),
            ])->values(),
        ])->values();

        $todos = [
            ['label' => __('New applications to review'), 'count' => $pending['newApps'], 'url' => $stageUrl('submitted'), 'icon' => 'heroicon-o-paper-airplane'],
            ['label' => __('Documents waiting for verification'), 'count' => $pending['docs'], 'url' => $kpis[2]['url'], 'icon' => 'heroicon-o-document-check'],
            ['label' => __('Visa targets to confirm'), 'count' => $pending['visa'],
                'url' => StudentResource::getUrl('index', ['filters' => ['visa_target_status' => ['value' => 'pending']]]), 'icon' => 'heroicon-o-identification'],
            ['label' => __('Unread contact messages'), 'count' => $pending['messages'], 'url' => $kpis[3]['url'], 'icon' => 'heroicon-o-envelope'],
        ];

        $actions = [
            ['label' => __('New job posting'), 'url' => JobVacancyResource::getUrl('create'), 'icon' => 'heroicon-o-plus', 'primary' => true],
            ['label' => __('Review applications'), 'url' => ApplicationResource::getUrl('index'), 'icon' => 'heroicon-o-paper-airplane', 'students' => true],
            ['label' => __('Verify documents'), 'url' => $kpis[2]['url'], 'icon' => 'heroicon-o-document-check', 'students' => true],
            ['label' => __('Website Settings'), 'url' => SiteSettings::getUrl(), 'icon' => 'heroicon-o-cog-6-tooth'],
            ['label' => __('View website'), 'url' => url('/'), 'icon' => 'heroicon-o-globe-alt', 'external' => true],
        ];

        // Admins without "manage students" only get the website side.
        $students = auth()->user()?->canManageStudents() ?? false;
        if (! $students) {
            $kpis    = [$kpis[3], $kpis[4]];
            $todos   = [$todos[3]];
            $actions = array_values(array_filter($actions, fn ($a) => empty($a['students'])));
        }

        return [
            'students'  => $students,
            'user'      => auth()->user(),
            'greeting'  => $this->greeting($now),
            'today'     => $now->translatedFormat('l, d F Y'),
            'pending'   => $pending,
            'kpis'      => $kpis,
            'pipeline'  => $pipeline,
            'exits'     => $exits,
            'chart'     => $chart,
            'chartMax'  => max(1, $chart->max(fn ($d) => max($d['apps'], $d['regs']))),
            'chartTotals' => ['apps' => array_sum($appSeries), 'regs' => array_sum($regSeries)],
            'map'       => $map,
            'todos'     => $todos,
            'feed'      => $this->feed($students),
            'actions'   => $actions,
        ];
    }

    private function kpi(string $label, int $value, ?Builder $created, string $icon, string $tone, string $url): array
    {
        $spark = $created ? array_values($this->daily(clone $created, 14)) : [];
        $thisWeek = array_sum(array_slice($spark, 7));
        $lastWeek = array_sum(array_slice($spark, 0, 7));

        return compact('label', 'value', 'icon', 'tone', 'url', 'spark') + [
            'delta'    => $created ? $thisWeek - $lastWeek : null,
            'thisWeek' => $thisWeek,
        ];
    }

    /** @return array<string,int> date (Y-m-d) => count, oldest first, zero-filled */
    private function daily(Builder $query, int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $rows = $query->where($query->getModel()->getTable().'.created_at', '>=', $from)
            ->selectRaw('DATE('.$query->getModel()->getTable().'.created_at) as d, count(*) as c')
            ->groupBy('d')->pluck('c', 'd');

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $from->copy()->addDays($i)->toDateString();
            $out[$d] = (int) ($rows[$d] ?? 0);
        }

        return $out;
    }

    private function greeting(Carbon $now): array
    {
        $h = $now->hour;

        return match (true) {
            $h < 11 => ['jp' => 'おはようございます', 'text' => __('Good morning')],
            $h < 15 => ['jp' => 'こんにちは', 'text' => __('Good afternoon')],
            $h < 18 => ['jp' => 'こんにちは', 'text' => __('Good evening')],
            default => ['jp' => 'こんばんは', 'text' => __('Good night')],
        };
    }

    private function feed(bool $students): Collection
    {
        $items = collect();

        foreach ($students ? Application::with('user', 'vacancy')->latest()->limit(6)->get() : [] as $a) {
            $items->push(['at' => $a->created_at, 'icon' => 'heroicon-o-paper-airplane', 'tone' => 'red',
                'text' => __(':name applied for :job', ['name' => $a->user?->name ?? '—', 'job' => $a->vacancy?->t('title') ?? '—']),
                'url' => ApplicationResource::getUrl('edit', ['record' => $a])]);
        }
        foreach ($students ? User::role('student')->latest()->limit(6)->get() : [] as $u) {
            $items->push(['at' => $u->created_at, 'icon' => 'heroicon-o-user-plus', 'tone' => 'sakura',
                'text' => __(':name created an account', ['name' => $u->name]),
                'url' => StudentResource::getUrl('view', ['record' => $u])]);
        }
        foreach ($students ? StudentDocument::with('user')->latest()->limit(6)->get() : [] as $d) {
            $items->push(['at' => $d->created_at, 'icon' => 'heroicon-o-document-arrow-up', 'tone' => 'kin',
                'text' => __(':name uploaded :doc', ['name' => $d->user?->name ?? '—', 'doc' => DocumentType::labelFor((string) $d->type)]),
                'url' => StudentDocumentResource::getUrl('edit', ['record' => $d])]);
        }
        foreach (ContactMessage::latest()->limit(6)->get() as $m) {
            $items->push(['at' => $m->created_at, 'icon' => 'heroicon-o-envelope', 'tone' => 'ai',
                'text' => __(':name sent a message', ['name' => $m->name ?? '—']),
                'url' => ContactMessageResource::getUrl('view', ['record' => $m])]);
        }

        return $items->filter(fn ($i) => $i['at'])->sortByDesc('at')->take(8)->values();
    }
}
