<?php

namespace App\Filament\Resources\Applications;

use App\Filament\Resources\Applications\Pages;
use App\Models\Application;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereIn('status', ['submitted', 'under_review'])->count();
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
            Section::make(__('Application'))->columns(2)->components([
                Select::make('status')
                    ->options(collect(Application::STATUSES)->mapWithKeys(fn ($s) => [$s => __('application.status.'.$s)]))
                    ->required()
                    ->native(false),
                Textarea::make('admin_notes')->label(__('Admin notes'))->rows(4)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('user.name')->label(__('Applicant'))->searchable()->weight('bold'),
            TextColumn::make('user.email')->label(__('Email'))->searchable()->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('vacancy.title')->label(__('Job'))
                ->getStateUsing(fn ($record) => $record->vacancy?->t('title') ?? '—')
                ->limit(40)->searchable(),
            TextColumn::make('vacancy.company.name')->label(__('Company'))->toggleable(),
            TextColumn::make('status')->badge()
                ->formatStateUsing(fn ($state) => __('application.status.'.$state))
                ->color(fn ($state) => Application::STATUS_COLORS[$state] ?? 'gray'),
            TextColumn::make('created_at')->label(__('Applied'))->dateTime('d M Y H:i')->sortable(),
        ])
        ->defaultSort('created_at', 'desc')
        ->filters([
            SelectFilter::make('status')
                ->options(collect(Application::STATUSES)->mapWithKeys(fn ($s) => [$s => __('application.status.'.$s)])),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'edit'  => Pages\EditApplication::route('/{record}/edit'),
        ];
    }
}
