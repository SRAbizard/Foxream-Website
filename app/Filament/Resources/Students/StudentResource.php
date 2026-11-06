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
                // A check after the name once the e-mail address is verified.
                TextColumn::make('name')->label(__('Name'))->searchable()->weight('bold')
                    ->icon(fn (User $r) => $r->email_verified_at ? 'heroicon-s-check-badge' : null)
                    ->iconPosition(\Filament\Support\Enums\IconPosition::After)
                    ->iconColor('success')
                    ->tooltip(fn (User $r) => $r->email_verified_at ? __('Email verified on :date', ['date' => $r->email_verified_at->isoFormat('ll')]) : __('Email not verified yet'))
                    ->description(fn (User $r) => $r->is_active === false ? __('Inactive') : null),
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
                // Accounts that never got going: to find sign-ups made just to try.
                \Filament\Tables\Filters\Filter::make('empty')
                    ->label(__('Empty accounts (unverified, nothing filled in, over 7 days old)'))
                    ->query(fn (Builder $query) => $query->whereNull('email_verified_at')
                        ->where('created_at', '<', now()->subDays(7))
                        ->doesntHave('applications')->doesntHave('studentDocuments')
                        ->whereDoesntHave('studentProfile', fn ($p) => $p->where(fn ($w) => $w->whereNotNull('full_name')->orWhereNotNull('visa_target_status')))),
                \Filament\Tables\Filters\TernaryFilter::make('is_active')->label(__('Active')),
            ])
            ->recordUrl(fn (User $r) => Pages\ViewStudent::getUrl(['record' => $r->id]))
            ->recordActions([
                \Filament\Actions\ActionGroup::make(self::accountActions()),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('deactivate')->label(__('Deactivate'))
                        ->icon('heroicon-o-no-symbol')->color('warning')
                        ->visible(fn () => self::isSuperadmin())
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(fn (User $u) => $u->forceFill(['is_active' => false])->save())),
                    \Filament\Actions\BulkAction::make('delete')->label(__('Delete accounts'))
                        ->icon('heroicon-o-trash')->color('danger')
                        ->visible(fn () => self::isSuperadmin())
                        ->requiresConfirmation()
                        ->modalHeading(__('Delete the selected accounts?'))
                        ->modalDescription(__('Their profile, CV, documents, applications and uploaded files are deleted for good. This cannot be undone.'))
                        ->action(function ($records) {
                            $records->each(fn (User $u) => \App\Support\AccountRemoval::delete($u));
                            \Filament\Notifications\Notification::make()->title(__(':count accounts deleted', ['count' => $records->count()]))->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    private static function isSuperadmin(): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    /** Account management for superadmins: verify e-mail, switch off, delete. */
    public static function accountActions(): array
    {
        return [
            \Filament\Actions\Action::make('verifyEmail')->label(__('Mark email as verified'))
                ->icon('heroicon-o-check-badge')->color('success')
                ->visible(fn (User $record) => self::isSuperadmin() && ! $record->email_verified_at)
                ->requiresConfirmation()
                ->action(fn (User $record) => $record->forceFill(['email_verified_at' => now()])->save()),
            \Filament\Actions\Action::make('toggleActive')
                ->label(fn (User $record) => $record->is_active === false ? __('Activate') : __('Deactivate'))
                ->icon(fn (User $record) => $record->is_active === false ? 'heroicon-o-check-circle' : 'heroicon-o-no-symbol')
                ->color('warning')
                ->visible(fn () => self::isSuperadmin())
                ->requiresConfirmation()
                ->modalDescription(fn (User $record) => $record->is_active === false ? null : __('The student can no longer log in. Their data stays.'))
                ->action(fn (User $record) => $record->forceFill(['is_active' => $record->is_active === false])->save()),
            \Filament\Actions\Action::make('deleteAccount')->label(__('Delete account'))
                ->icon('heroicon-o-trash')->color('danger')
                ->visible(fn () => self::isSuperadmin())
                ->requiresConfirmation()
                ->modalHeading(fn (User $record) => __('Delete :name?', ['name' => $record->name]))
                ->modalDescription(__('Their profile, CV, documents, applications and uploaded files are deleted for good. This cannot be undone.'))
                ->action(function (User $record, $livewire) {
                    \App\Support\AccountRemoval::delete($record);
                    \Filament\Notifications\Notification::make()->title(__('Account deleted'))->success()->send();
                    if ($livewire instanceof Pages\ViewStudent) {
                        $livewire->redirect(Pages\ListStudents::getUrl());
                    }
                }),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'view'  => Pages\ViewStudent::route('/{record}'),
        ];
    }
}
