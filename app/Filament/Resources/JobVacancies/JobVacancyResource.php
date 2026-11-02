<?php

namespace App\Filament\Resources\JobVacancies;

use App\Filament\Resources\JobVacancies\Pages;
use App\Filament\Support\TranslatableTabs;
use App\Models\Company;
use App\Models\JobCategory;
use App\Models\JobVacancy;
use App\Models\VisaCategory;
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
        return $schema->components([
            Section::make(__('Basics'))->columns(2)->components([
                TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(120)
                    ->live(onBlur: true)->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state ?? ''))),
                // Companies grouped by their TSK: TSK → company → job posting.
                Select::make('company_id')->label(__('Company'))->required()
                    ->options(fn () => Company::with('tsk')->orderBy('name')->get()
                        ->groupBy(fn ($c) => $c->tsk?->name ?? __('No TSK'))
                        ->sortKeys()
                        ->map(fn ($companies) => $companies->pluck('name', 'id')->all())
                        ->all())
                    ->searchable(),
                Select::make('job_category_id')->label(__('Job Category'))
                    ->options(fn () => JobCategory::orderBy('sort_order')->get()->mapWithKeys(fn ($c) => [$c->id => $c->t('name')]))->searchable(),
                Select::make('visa_category_id')->label(__('Visa Category'))
                    ->options(fn () => VisaCategory::orderBy('sort_order')->get()->mapWithKeys(fn ($c) => [$c->id => $c->t('name')]))->searchable(),
                Select::make('employment_type')
                    ->options([
                        'fulltime'   => __('Full-time'),
                        'parttime'   => __('Part-time'),
                        'contract'   => __('Contract'),
                        'internship' => __('Internship'),
                    ])->default('fulltime')->required(),
                TextInput::make('positions')->numeric()->default(1)->minValue(1),
            ]),
            TranslatableTabs::for('title', TextInput::class, label: __('Title'), required: true),
            TranslatableTabs::for('description', RichEditor::class, label: __('Description'), required: true),
            TranslatableTabs::for('requirements', RichEditor::class, label: __('Requirements')),
            TranslatableTabs::for('benefits', RichEditor::class, label: __('Benefits')),
            Section::make(__('Job detail document'))
                ->description(__('Upload the full job-spec PDF (or DOC/image). Candidates can download it from the public job-detail page.'))
                ->components([
                    FileUpload::make('detail_pdf_path')
                        ->label(__('Job detail file'))
                        ->disk('public')
                        ->directory('jobs/details')
                        ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'])
                        ->maxSize(10240) // 10MB
                        ->columnSpanFull(),
                ]),
            static::jobOrderSection(),
            Section::make(__('Location'))->columns(2)->components([
                TextInput::make('location_city')->label(__('City'))->maxLength(80),
                TextInput::make('location_prefecture')->label(__('Prefecture'))->maxLength(80),
            ]),
            Section::make(__('Salary'))->columns(4)->components([
                TextInput::make('salary_min')->numeric()->label(__('Min')),
                TextInput::make('salary_max')->numeric()->label(__('Max')),
                Select::make('salary_currency')->options(['JPY' => 'JPY', 'IDR' => 'IDR', 'USD' => 'USD'])->default('JPY'),
                Select::make('salary_period')->options([
                    'monthly' => __('Monthly'),
                    'yearly'  => __('Yearly'),
                    'hourly'  => __('Hourly'),
                ])->default('monthly'),
            ]),
            Section::make(__('Publishing'))->columns(3)->components([
                DateTimePicker::make('published_at')->seconds(false)->default(now()),
                DatePicker::make('expires_at'),
                Toggle::make('is_featured')->inline(false),
            ]),
        ]);
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
            ->description(__('Fill this in to generate the "Terjemahan Job Order" PDF (buttons at the top of this page after saving). Empty fields are left out. In long fields, start a line with "- " to make a bullet point.'))
            ->collapsible()
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
