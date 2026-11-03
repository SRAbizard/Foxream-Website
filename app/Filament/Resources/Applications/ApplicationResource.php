<?php

namespace App\Filament\Resources\Applications;

use App\Filament\Resources\Applications\Pages;
use App\Models\Application;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Filament\Support\ForStudentManagers;
use UnitEnum;

class ApplicationResource extends Resource
{
    use ForStudentManagers;

    protected static ?string $model = Application::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string | UnitEnum | null $navigationGroup = 'Recruitment';
    protected static ?int $navigationSort = 7;

    public static function getNavigationLabel(): string { return __('Applications'); }
    public static function getNavigationGroup(): ?string { return __('Recruitment'); }
    public static function getModelLabel(): string       { return __('Application'); }
    public static function getPluralModelLabel(): string { return __('Applications'); }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'submitted')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string { return 'primary'; }

    public static function canCreate(): bool { return false; }

    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name', 'user.email', 'vacancy.title->id', 'vacancy.title->en'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return ($record->user?->name ?? '—').' → '.($record->vacancy?->t('title') ?? '—');
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            __('Status')  => __('application.status.'.$record->status),
            __('Company') => $record->vacancy?->company?->name ?? '—',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // The step is only shown here: the buttons at the top move it and notify the student.
            Section::make(__('Application'))->components([
                View::make('filament.admin.application-progress'),
                Textarea::make('admin_notes')->label(__('Admin notes'))->rows(4),
            ]),

            // After passing, each section opens once the application reaches its step.
            Section::make(__('Employment contract'))->columns(2)
                ->description(__('Upload the contract for the student to sign; they upload the signed copy.'))
                ->visible(fn (?Application $record) => $record?->reached('passed'))
                ->components([
                    self::file('contract_path', __('Contract to sign')),
                    self::file('signed_contract_path', __('Signed contract (from the student)'))
                        ->helperText(fn (?Application $record) => $record?->signed_contract_at
                            ? __('Uploaded :date', ['date' => $record->signed_contract_at->format('d M Y H:i')]) : null),
                ]),
            Section::make('COE')->columns(2)
                ->visible(fn (?Application $record) => $record?->reached('contract'))
                ->components([
                    DatePicker::make('coe_issued_at')->label(__('COE issued on'))->native(false)->displayFormat('d M Y'),
                    self::file('coe_path', __('COE file')),
                ]),
            Section::make(__('Visa'))->columns(2)
                ->visible(fn (?Application $record) => $record?->reached('coe'))
                ->components([
                    DatePicker::make('visa_issued_at')->label(__('Visa issued on'))->native(false)->displayFormat('d M Y'),
                    self::file('visa_path', __('Visa file')),
                ]),
            Section::make(__('Departure'))->columns(2)
                ->visible(fn (?Application $record) => $record?->reached('visa'))
                ->components([
                    DateTimePicker::make('departure_at')->label(__('Departure (WIB)'))
                        ->seconds(false)->native(false)->displayFormat('d M Y H:i'),
                    self::file('ticket_path', __('Flight ticket')),
                    Textarea::make('flight_info')->label(__('Flight details'))->rows(3)->columnSpanFull()
                        ->placeholder(__('Airline, flight number, from Soekarno-Hatta (CGK) to …, meeting point and time at the airport')),
                ]),
        ]);
    }

    private static function file(string $column, string $label): FileUpload
    {
        return FileUpload::make($column)->label($label)
            ->disk('public')->directory('students/applications')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(10240)
            ->openable()->downloadable();
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('user.name')->label(__('Applicant'))->searchable()->weight('bold'),
            TextColumn::make('user.email')->label(__('Email'))->searchable()->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('vacancy.title')->label(__('Job'))
                ->getStateUsing(fn ($record) => $record->vacancy?->t('title') ?? '—')
                ->limit(40)->searchable(),
            TextColumn::make('vacancy.company.name')->label(__('Company'))->toggleable()
                ->description(fn ($record) => $record->vacancy?->company?->tsk?->name ? __('TSK').': '.$record->vacancy->company->tsk->name : null),
            TextColumn::make('status')->label(__('Step'))->badge()
                ->formatStateUsing(fn ($state) => __('application.status.'.$state))
                ->color(fn ($state) => Application::STATUS_COLORS[$state] ?? 'gray'),
            TextColumn::make('next_schedule')->label(__('Next schedule'))
                ->state(fn (Application $record) => ($s = $record->schedules->first(fn ($s) => $s->isUpcoming()))
                    ? $s->type_label.' · '.($s->starts_at?->format('d M H:i') ?? __('To follow'))
                    : null)
                ->placeholder('—'),
            TextColumn::make('created_at')->label(__('Applied'))->dateTime('d M Y H:i')->sortable(),
        ])
        ->modifyQueryUsing(fn ($query) => $query->with('user', 'vacancy.company.tsk', 'schedules'))
        ->defaultSort('created_at', 'desc')
        ->recordActions(\App\Filament\Support\DeleteActions::row(
            __('Its schedules and the contract, COE, visa and ticket files are deleted too. This cannot be undone.'),
            fn () => auth()->user()?->hasRole('superadmin'),
        ))
        ->toolbarActions(\App\Filament\Support\DeleteActions::bulk(
            __('Their schedules and the contract, COE, visa and ticket files are deleted too. This cannot be undone.'),
            fn () => auth()->user()?->hasRole('superadmin'),
        ))
        ->filters([
            SelectFilter::make('status')->label(__('Step'))
                ->options(collect(Application::STATUSES)->mapWithKeys(fn ($s) => [$s => __('application.status.'.$s)])),
            SelectFilter::make('job_vacancy_id')->label(__('Job'))->searchable()
                ->options(fn () => \App\Models\JobVacancy::orderByDesc('published_at')->get()->mapWithKeys(fn ($v) => [$v->id => $v->t('title')])),
            SelectFilter::make('tsk')->label(__('TSK'))->searchable()
                ->options(fn () => \App\Models\Tsk::orderBy('name')->pluck('name', 'id'))
                ->query(fn ($query, array $data) => $query->when($data['value'] ?? null,
                    fn ($q, $id) => $q->whereHas('vacancy.company', fn ($c) => $c->where('tsk_id', $id)))),
            SelectFilter::make('company')->label(__('Company'))->searchable()
                ->options(fn () => \App\Models\Company::orderBy('name')->pluck('name', 'id'))
                ->query(fn ($query, array $data) => $query->when($data['value'] ?? null,
                    fn ($q, $id) => $q->whereHas('vacancy', fn ($v) => $v->where('company_id', $id)))),
            SelectFilter::make('visa')->label(__('Visa'))
                ->options(fn () => \App\Models\VisaCategory::orderBy('sort_order')->get()->mapWithKeys(fn ($v) => [$v->id => $v->t('name')]))
                ->query(fn ($query, array $data) => $query->when($data['value'] ?? null,
                    fn ($q, $id) => $q->whereHas('vacancy', fn ($v) => $v->where('visa_category_id', $id)))),
            SelectFilter::make('field')->label(__('Job Category'))
                ->options(fn () => \App\Models\JobCategory::orderBy('sort_order')->get()->mapWithKeys(fn ($c) => [$c->id => $c->t('name')]))
                ->query(fn ($query, array $data) => $query->when($data['value'] ?? null,
                    fn ($q, $id) => $q->whereHas('vacancy', fn ($v) => $v->where('job_category_id', $id)))),
            \Filament\Tables\Filters\Filter::make('applied')
                ->schema([
                    DatePicker::make('from')->label(__('Applied from'))->native(false),
                    DatePicker::make('until')->label(__('Applied until'))->native(false),
                ])
                ->query(fn ($query, array $data) => $query
                    ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                    ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            \Filament\Tables\Filters\Filter::make('upcoming')->label(__('Has an upcoming schedule'))
                ->query(fn ($query) => $query->whereHas('schedules', fn ($s) => $s->whereNull('result')
                    ->where(fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '>', now())))),
        ]);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\SchedulesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'edit'  => Pages\EditApplication::route('/{record}/edit'),
        ];
    }
}
