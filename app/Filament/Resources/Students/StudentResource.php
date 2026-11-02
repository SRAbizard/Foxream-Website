<?php

namespace App\Filament\Resources\Students;

use App\Filament\Resources\Students\Pages;
use App\Models\User;
use App\Models\VisaCategory;
use App\Support\StudentDocumentProgress;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Support\ForStudentManagers;
use UnitEnum;

class StudentResource extends Resource
{
    use ForStudentManagers;

    protected static ?string $model = User::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static string | UnitEnum | null $navigationGroup = 'Students';
    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string { return __('Students'); }
    public static function getNavigationGroup(): ?string { return __('Students'); }
    public static function getModelLabel(): string      { return __('Student'); }
    public static function getPluralModelLabel(): string { return __('Students'); }

    public static function canCreate(): bool { return false; }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            __('Email')  => $record->email,
            __('Status') => $record->studentProfile?->visa_target_status
                ? __('visa.target.status.'.$record->studentProfile->visa_target_status)
                : __('Not selected'),
        ];
    }

    public static function getGlobalSearchResultUrl($record): string
    {
        return Pages\ViewStudent::getUrl(['record' => $record->getKey()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('roles', fn ($q) => $q->where('name', 'student'))
            ->with('studentProfile.primaryVisa', 'educations')
            ->withCount(['applications', 'studentDocuments']);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = \App\Models\StudentProfile::whereIn('visa_target_status', ['pending', 'changed'])->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string { return 'warning'; }

    public static function form(Schema $schema): Schema
    {
        // Students aren't edited from this resource — admin uses
        // StudentDocumentResource and other tools for that. The form
        // is required by Filament but never rendered (no Edit page).
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->weight('bold'),
                TextColumn::make('email')->label(__('Email'))->searchable()->copyable(),
                TextColumn::make('phone')->label(__('Phone'))->placeholder('—')->toggleable(),

                TextColumn::make('cv_completeness')
                    ->label(__('CV'))
                    ->state(fn (User $r): string => \App\Support\CvCompleteness::for($r)['pct'].'%')
                    ->badge()
                    ->color(fn (User $r) => \App\Support\CvCompleteness::for($r)['complete'] ? 'success' : 'warning'),

                TextColumn::make('document_progress')
                    ->label(__('Documents'))
                    ->state(function (User $r): string {
                        $p = StudentDocumentProgress::for($r);
                        return $p['verified_count'].' / '.$p['required_count'];
                    })
                    ->description(function (User $r): ?string {
                        $p = StudentDocumentProgress::for($r);
                        if ($p['required_count'] === 0) return __('No requirements');
                        return $p['pct'].'% '.__('verified')
                            .($p['using_default'] ? ' · '.__('default set') : '');
                    })
                    ->badge()
                    ->color(function (User $r) {
                        $p = StudentDocumentProgress::for($r);
                        return match (true) {
                            $p['pct'] >= 100 => 'success',
                            $p['pct'] >= 50  => 'info',
                            $p['pct'] > 0    => 'warning',
                            default          => 'gray',
                        };
                    }),

                TextColumn::make('studentProfile.primaryVisa.name')
                    ->label(__('Visa target'))
                    ->state(function (User $r): string {
                        return $r->studentProfile?->primaryVisa?->t('name') ?? '—';
                    })
                    ->description(function (User $r): ?string {
                        $s = $r->studentProfile?->visa_target_status;
                        if (! $s) return __('Not selected');
                        return __('visa.target.status.'.$s);
                    })
                    ->badge()
                    ->color(function (User $r) {
                        $s = $r->studentProfile?->visa_target_status;
                        return \App\Models\StudentProfile::VISA_TARGET_STATUS_COLORS[$s] ?? 'gray';
                    })
                    ->wrap(),

                TextColumn::make('applications_count')->label(__('Applications'))
                    ->numeric()->alignCenter()->sortable(),
                TextColumn::make('student_documents_count')->label(__('Docs uploaded'))
                    ->numeric()->alignCenter()->sortable()->toggleable(),

                TextColumn::make('email_verified_at')->label(__('Verified'))
                    ->dateTime('d M Y')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label(__('Registered'))
                    ->dateTime('d M Y')->sortable(),
                TextColumn::make('last_login_at')->label(__('Last login'))
                    ->dateTime('d M Y H:i')->placeholder('—')->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('visa_target_status')
                    ->label(__('Visa target status'))
                    ->options(collect(\App\Models\StudentProfile::VISA_TARGET_STATUSES)
                        ->mapWithKeys(fn ($s) => [$s => __('visa.target.status.'.$s)])
                        ->prepend(__('Not selected'), 'none'))
                    ->query(function (Builder $q, array $data) {
                        if (! filled($data['value'])) return $q;
                        if ($data['value'] === 'none') {
                            return $q->whereDoesntHave('studentProfile',
                                fn ($qq) => $qq->whereNotNull('visa_target_status'));
                        }
                        return $q->whereHas('studentProfile',
                            fn ($qq) => $qq->where('visa_target_status', $data['value']));
                    }),
                SelectFilter::make('visa')
                    ->label(__('Targeting visa'))
                    ->options(fn () => VisaCategory::orderBy('sort_order')->get()
                        ->mapWithKeys(fn ($v) => [$v->id => $v->t('name')]))
                    ->query(function (Builder $q, array $data) {
                        if (! filled($data['value'])) return $q;
                        return $q->whereHas('studentProfile',
                            fn ($qq) => $qq->where('primary_visa_category_id', $data['value']));
                    }),
                SelectFilter::make('email_verified')
                    ->label(__('Email status'))
                    ->options(['verified' => __('Verified'), 'unverified' => __('Unverified')])
                    ->query(function (Builder $q, array $data) {
                        if ($data['value'] === 'verified')   return $q->whereNotNull('email_verified_at');
                        if ($data['value'] === 'unverified') return $q->whereNull('email_verified_at');
                        return $q;
                    }),
                SelectFilter::make('completion')
                    ->label(__('Document completion'))
                    ->options([
                        'complete'   => __('Complete (100%)'),
                        'in_progress'=> __('In progress (1-99%)'),
                        'none'       => __('Not started (0%)'),
                    ]),
            ])
            ->recordUrl(fn (User $r) => Pages\ViewStudent::getUrl(['record' => $r->id]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'view'  => Pages\ViewStudent::route('/{record}'),
        ];
    }
}
