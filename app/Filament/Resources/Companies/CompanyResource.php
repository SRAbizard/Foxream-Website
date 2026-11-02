<?php

namespace App\Filament\Resources\Companies;

use App\Filament\Resources\Companies\Pages;
use App\Filament\Support\TranslatableTabs;
use App\Models\Company;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';
    protected static string | UnitEnum | null $navigationGroup = 'Recruitment';
    protected static ?int $navigationSort = 1;

    /** Shown before deleting: a company's jobs, and the applications for them, go with it. */
    private const DELETE_WARNING = 'Its job postings and every application to them are deleted too. This cannot be undone.';

    public static function getNavigationLabel(): string { return __('Companies'); }
    public static function getNavigationGroup(): ?string { return __('Recruitment'); }
    public static function getModelLabel(): string { return __('Company'); }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'industry', 'city', 'country'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            __('Industry') => $record->industry ?? '—',
            __('Location') => trim(($record->city ?? '').', '.($record->country ?? ''), ', '),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Basics'))->columns(2)->components([
                TextInput::make('name')->required()->maxLength(120),
                TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(120)
                    ->live(onBlur: true)->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('industry')->maxLength(80),
                TextInput::make('website')->url()->prefix('https://')->maxLength(191),
                TextInput::make('country')->default('Japan')->maxLength(80),
                TextInput::make('city')->maxLength(80),
            ]),
            TranslatableTabs::for('description', Textarea::class, label: __('Description'),
                componentMods: ['rows' => [5]]),
            Section::make()->columns(2)->components([
                FileUpload::make('logo_path')->image()->disk('public')->directory('companies')->avatar(),
                Toggle::make('is_verified')->inline(false),
                Toggle::make('is_active')->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            ImageColumn::make('logo_path')->disk('public')->circular()->label(''),
            TextColumn::make('name')->searchable()->sortable()->weight('bold'),
            TextColumn::make('industry')->toggleable(),
            TextColumn::make('city')->toggleable(),
            TextColumn::make('country')->toggleable(),
            IconColumn::make('is_verified')->boolean()->label(__('Verified')),
            TextColumn::make('vacancies_count')->counts('vacancies')->label(__('Jobs')),
        ])
        ->defaultSort('name')
        ->filters([
            \Filament\Tables\Filters\TernaryFilter::make('is_verified')->label(__('Verified')),
            \Filament\Tables\Filters\TernaryFilter::make('is_active')->label(__('Active')),
            \Filament\Tables\Filters\SelectFilter::make('industry')->label(__('Industry'))
                ->options(fn () => Company::whereNotNull('industry')->distinct()->orderBy('industry')->pluck('industry', 'industry')),
            \Filament\Tables\Filters\SelectFilter::make('country')->label(__('Country'))
                ->options(fn () => Company::whereNotNull('country')->distinct()->orderBy('country')->pluck('country', 'country')),
            \Filament\Tables\Filters\Filter::make('without_jobs')->label(__('No job postings'))
                ->query(fn ($query) => $query->doesntHave('vacancies')),
        ])
        ->recordActions(\App\Filament\Support\DeleteActions::row(__(self::DELETE_WARNING)))
        ->toolbarActions(\App\Filament\Support\DeleteActions::bulk(__(self::DELETE_WARNING)));
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit'   => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
