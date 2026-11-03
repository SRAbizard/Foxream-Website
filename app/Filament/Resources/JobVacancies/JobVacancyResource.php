<?php

namespace App\Filament\Resources\JobVacancies;

use App\Filament\Resources\JobVacancies\Pages;
use App\Models\Company;
use App\Models\JobCategory;
use App\Models\JobVacancy;
use App\Models\VisaCategory;
use App\Support\JapaneseTranslator;
use App\Support\JobOrder;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class JobVacancyResource extends Resource
{
    protected static ?string $model = JobVacancy::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-briefcase';
    protected static string | UnitEnum | null $navigationGroup = 'Recruitment';
    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string { return __('Job Vacancies'); }
    public static function getNavigationGroup(): ?string { return __('Recruitment'); }
    public static function getModelLabel(): string { return __('Job Vacancy'); }

    public static function getGloballySearchableAttributes(): array
    {
        return ['slug', 'title->id', 'title->en', 'title->ja', 'location_city', 'location_prefecture'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->t('title');
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            __('Company') => $record->company?->name ?? '—',
            __('Visa')    => $record->visaCategory?->t('name') ?? '—',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        // The admin writes in Indonesian only; English and Japanese are
        // translated on save (completeData) and can be corrected, folded away.
        $editor = fn (string $path, string $label) => RichEditor::make($path)->label($label)
            ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList', 'h3', 'undo', 'redo']);
        $translations = fn (string $locale) => [
            TextInput::make("title.{$locale}")->label(__('Job title'))->maxLength(191),
            $editor("description.{$locale}", __('Job description')),
            $editor("requirements.{$locale}", __('Requirements')),
            $editor("benefits.{$locale}", __('Benefits')),
        ];

        return $schema->components([
            Section::make(__('The job'))->columns(2)->components([
                // Companies grouped by their TSK: TSK → company → job posting.
                Select::make('company_id')->label(__('Company'))->required()
                    ->options(fn () => Company::with('tsk')->orderBy('name')->get()
                        ->groupBy(fn ($c) => $c->tsk?->name ?? __('No TSK'))
                        ->sortKeys()
                        ->map(fn ($companies) => $companies->pluck('name', 'id')->all())
                        ->all())
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, $get, $set) {
                        if (blank($get('location_city')) && ($city = Company::find($state)?->city)) {
                            $set('location_city', $city);
                        }
                    }),
                TextInput::make('title.id')->label(__('Job title'))->required()->maxLength(191)
                    ->placeholder(__('e.g. Food factory operator')),
                Select::make('job_category_id')->label(__('Job Category'))->required()
                    ->options(fn () => JobCategory::orderBy('sort_order')->get()->mapWithKeys(fn ($c) => [$c->id => $c->t('name')]))->searchable()
                    ->helperText(__('Students see this job as a recommendation in this field.')),
                Select::make('visa_category_id')->label(__('Visa Category'))->required()
                    ->options(fn () => VisaCategory::orderBy('sort_order')->get()->mapWithKeys(fn ($c) => [$c->id => $c->t('name')]))->searchable(),
                TextInput::make('location_city')->label(__('City'))->maxLength(80),
                TextInput::make('location_prefecture')->label(__('Prefecture'))->maxLength(80),
                TextInput::make('positions')->label(__('Positions'))->numeric()->default(1)->minValue(1),
                Select::make('employment_type')->label(__('Employment type'))
                    ->options([
                        'fulltime'   => __('Full-time'),
                        'parttime'   => __('Part-time'),
                        'contract'   => __('Contract'),
                        'internship' => __('Internship'),
                    ])->default('fulltime')->required(),
            ]),
            Section::make(__('Salary'))->columns(4)->components([
                TextInput::make('salary_min')->numeric()->label(__('Min')),
                TextInput::make('salary_max')->numeric()->label(__('Max')),
                Select::make('salary_currency')->label(__('Currency'))->options(['JPY' => 'JPY', 'IDR' => 'IDR', 'USD' => 'USD'])->default('JPY'),
                Select::make('salary_period')->label(__('Per'))->options([
                    'monthly' => __('Monthly'),
                    'yearly'  => __('Yearly'),
                    'hourly'  => __('Hourly'),
                ])->default('monthly'),
            ]),
            Section::make(__('Description'))
                ->description(JapaneseTranslator::enabled()
                    ? __('Write in Indonesian. English and Japanese are translated automatically when you save.')
                    : __('Write in Indonesian. Visitors on the English and Japanese site see this text until a translation is added below.'))
                ->components([
                    $editor('description.id', __('Job description'))->required(),
                    $editor('requirements.id', __('Requirements')),
                    $editor('benefits.id', __('Benefits')),
                ]),
            Section::make(__('English & Japanese'))
                ->description(__('Optional. Correct the automatic translation here. Empty a field to translate it again from Indonesian on the next save.'))
                ->collapsed()
                ->components([
                    Tabs::make('translations')->tabs([
                        Tab::make('EN · English')->schema($translations('en')),
                        Tab::make('JA · 日本語')->schema($translations('ja')),
                    ]),
                ]),
            static::jobOrderSection(),
            Section::make(__('More settings'))->collapsed()->columns(3)->components([
                DateTimePicker::make('published_at')->label(__('Published at'))->seconds(false)->default(now()),
                DatePicker::make('expires_at')->label(__('Closes on')),
                Toggle::make('is_featured')->label(__('Featured'))->inline(false),
                FileUpload::make('detail_pdf_path')
                    ->label(__('Job detail file'))
                    ->helperText(__('Upload the full job-spec PDF (or DOC/image). Candidates can download it from the public job-detail page.'))
                    ->disk('public')
                    ->directory('jobs/details')
                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'])
                    ->maxSize(10240) // 10MB
                    ->columnSpanFull(),
                TextInput::make('slug')->label(__('Link (slug)'))->unique(ignoreRecord: true)->maxLength(120)
                    ->helperText(__('Made from the title when left empty.'))
                    ->dehydrateStateUsing(fn ($state) => Str::slug($state ?? ''))
                    ->columnSpanFull(),
            ]),
        ]);
    }

    /** Translatable columns: written in Indonesian, translated to the other locales. */
    public const TRANSLATED = ['title', 'description', 'requirements', 'benefits'];

    /**
     * Before saving: a slug from the title when there is none, and English
     * and Japanese for every text the admin left empty in those languages.
     */
    public static function completeData(array $data, ?JobVacancy $record = null): array
    {
        if (blank($data['slug'] ?? null)) {
            $data['slug'] = static::uniqueSlug((string) ($data['title']['id'] ?? ''), $record?->getKey());
        }

        if (! JapaneseTranslator::enabled()) {
            return $data;
        }

        $text = fn ($value) => trim(strip_tags((string) $value));
        foreach (['en' => 'EN-US', 'ja' => 'JA'] as $locale => $target) {
            $missing = collect(self::TRANSLATED)
                ->filter(fn ($f) => $text($data[$f]['id'] ?? '') !== '' && $text($data[$f][$locale] ?? '') === '')
                ->values();
            if ($missing->isEmpty()) {
                continue;
            }

            try {
                // One call per language; HTML mode keeps the editor's formatting.
                $done = JapaneseTranslator::many($missing->map(fn ($f) => (string) $data[$f]['id'])->all(), $target, html: true);
            } catch (\RuntimeException $e) {
                \Filament\Notifications\Notification::make()->warning()
                    ->title(__('Saved without automatic translation'))
                    ->body($e->getMessage().' '.__('The Indonesian text is shown until a translation is added.'))
                    ->send();

                return $data;
            }

            foreach ($missing as $i => $field) {
                $data[$field][$locale] = $field === 'title' ? html_entity_decode($done[$i], ENT_QUOTES | ENT_HTML5) : $done[$i];
            }
        }

        return $data;
    }

    protected static function uniqueSlug(string $title, ?int $ignore = null): string
    {
        $base = Str::limit(Str::slug($title), 100, '') ?: 'lowongan';
        $slug = $base;
        for ($i = 2; JobVacancy::where('slug', $slug)->when($ignore, fn ($q) => $q->whereKeyNot($ignore))->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /**
     * The structured Job Order (App\Support\JobOrder) behind the generated
     * "Terjemahan Job Order" PDF. Labels stay in Indonesian with the
     * Japanese term, because that is the language of the document.
     */
    protected static function jobOrderSection(): Section
    {
        $field = function (string $section, string $name, array $def): array {
            [$label, $jp, $type] = $def;
            $path  = "job_order.{$section}.{$name}";
            $label = "{$label} ({$jp})";

            return match ($type) {
                'textarea'  => [Textarea::make($path)->label($label)->placeholder($def[3] ?? null)->rows(3)->maxLength(2000)->columnSpanFull()],
                'select'    => [Select::make($path)->label($label)->options(JobOrder::EMPLOYMENT)->native(false)],
                'checklist' => [
                    CheckboxList::make($path)->label($label)->options(JobOrder::INSURANCE)->columns(2)->columnSpanFull(),
                    TextInput::make("job_order.{$section}.insurance_note")->label(__('Insurance notes'))->maxLength(300)->columnSpanFull(),
                ],
                default     => [TextInput::make($path)->label($label)->placeholder($def[3] ?? null)->maxLength(500)],
            };
        };

        return Section::make(__('Job Order'))
            ->description(__('Only what candidates need to know. Fill this in to generate the Job Order PDF (button at the top of this page after saving); empty fields are left out. In long fields, start a line with "- " to make a bullet point.'))
            ->collapsed()
            ->columns(2)
            ->components([
                TextInput::make('job_order_code')->label(__('Job Order code'))->placeholder('TG - PM')->maxLength(40)
                    ->helperText(__('Shown in brackets after the company name in the PDF title.')),
                Toggle::make('job_order_public')->label(__('Show the Job Order PDF on the public job page'))->inline(false),
                Tabs::make('job-order')->columnSpanFull()->tabs(
                    collect(JobOrder::sections())->map(fn (array $s, string $key) => Tab::make($s['title'])->columns(2)->schema(
                        collect($s['fields'])->flatMap(fn (array $def, string $name) => $field($key, $name, $def))->all()
                    ))->values()->all()
                ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->label(__('Title'))
                ->getStateUsing(fn ($record) => $record->t('title'))
                ->searchable(query: fn ($q, $search) => $q->where('title->id', 'like', "%{$search}%")->orWhere('title->en', 'like', "%{$search}%"))
                ->limit(50)->wrap(),
            TextColumn::make('company.name')->label(__('Company'))->searchable()->toggleable()
                ->description(fn ($record) => $record->company?->tsk?->name ? __('TSK').': '.$record->company->tsk->name : null),
            TextColumn::make('jobCategory.name')->label(__('Category'))
                ->getStateUsing(fn ($record) => $record->jobCategory?->t('name') ?? '—')
                ->badge(),
            TextColumn::make('visaCategory.name')->label(__('Visa'))
                ->getStateUsing(fn ($record) => $record->visaCategory?->t('name') ?? '—')
                ->badge()->color('primary'),
            TextColumn::make('location_city')->label(__('Location'))->toggleable(),
            TextColumn::make('salary_range')->label(__('Salary'))
                ->formatStateUsing(fn ($state, $record) => $record->salary_range ?? '—')
                ->color('success'),
            TextColumn::make('applications_count')->counts('applications')->label(__('Applicants'))->badge(),
            IconColumn::make('is_featured')->boolean()->label('★'),
            TextColumn::make('published_at')->dateTime('d M Y')->sortable()->toggleable(),
        ])
        ->defaultSort('published_at', 'desc')
        ->filters([
            SelectFilter::make('visa_category_id')->label(__('Visa Category'))
                ->options(fn () => VisaCategory::all()->mapWithKeys(fn ($v) => [$v->id => $v->t('name')])),
            SelectFilter::make('job_category_id')->label(__('Job Category'))
                ->options(fn () => JobCategory::all()->mapWithKeys(fn ($c) => [$c->id => $c->t('name')])),
            SelectFilter::make('tsk')->label(__('TSK'))->searchable()
                ->options(fn () => \App\Models\Tsk::orderBy('name')->pluck('name', 'id'))
                ->query(fn ($query, array $data) => $query->when($data['value'] ?? null,
                    fn ($q, $id) => $q->whereHas('company', fn ($c) => $c->where('tsk_id', $id)))),
            SelectFilter::make('company_id')->label(__('Company'))
                ->options(fn () => Company::orderBy('name')->pluck('name', 'id'))->searchable(),
            SelectFilter::make('publication')->label(__('Status'))
                ->options([
                    'published' => __('Published'),
                    'draft'     => __('Draft'),
                    'expired'   => __('Expired'),
                ])
                ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                    'published' => $query->published(),
                    'draft'     => $query->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '>', now())),
                    'expired'   => $query->whereNotNull('expires_at')->where('expires_at', '<', now()->toDateString()),
                    default     => $query,
                }),
            \Filament\Tables\Filters\TernaryFilter::make('is_featured')->label(__('Featured')),
            \Filament\Tables\Filters\Filter::make('without_applicants')->label(__('No applicants yet'))
                ->query(fn ($query) => $query->doesntHave('applications')),
        ])
        ->recordActions(\App\Filament\Support\DeleteActions::row(__('Every application to this job is deleted too. This cannot be undone.')))
        ->toolbarActions(\App\Filament\Support\DeleteActions::bulk(__('Every application to these jobs is deleted too. This cannot be undone.')));
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListJobVacancies::route('/'),
            'create' => Pages\CreateJobVacancy::route('/create'),
            'edit'   => Pages\EditJobVacancy::route('/{record}/edit'),
        ];
    }
}
