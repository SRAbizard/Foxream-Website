<?php

namespace App\Filament\Resources\Tsks;

use App\Filament\Resources\Tsks\Pages;
use App\Filament\Support\DeleteActions;
use App\Models\Tsk;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * TSK (登録支援機関, registered support organisations), first in the
 * recruitment chain: TSK → company → job posting.
 */
class TskResource extends Resource
{
    protected static ?string $model = Tsk::class;
    protected static ?string $slug = 'tsk';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-lifebuoy';
    protected static string | UnitEnum | null $navigationGroup = 'Recruitment';
    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string  { return __('TSK'); }
    public static function getNavigationGroup(): ?string { return __('Recruitment'); }
    public static function getModelLabel(): string       { return __('TSK'); }
    public static function getPluralModelLabel(): string { return __('TSK'); }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'name_ja', 'registration_number'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('TSK (registered support organisation)'))->columns(2)->components([
                TextInput::make('name')->label(__('Name'))->required()->maxLength(160),
                TextInput::make('name_ja')->label(__('Name in Japanese'))->maxLength(160)->placeholder('株式会社〇〇'),
                TextInput::make('registration_number')->label(__('Registration number'))->maxLength(40)->placeholder('19登-000123'),
                TextInput::make('city')->label(__('City'))->maxLength(80),
                Textarea::make('address')->label(__('Address'))->rows(2)->columnSpanFull(),
            ]),
            Section::make(__('Contact'))->columns(3)->components([
                TextInput::make('contact_person')->label(__('Contact person'))->maxLength(120),
                TextInput::make('phone')->label(__('Phone'))->tel()->maxLength(40),
                TextInput::make('email')->label(__('Email'))->email()->maxLength(160),
            ]),
            Section::make()->columns(2)->components([
                Textarea::make('notes')->label(__('Notes'))->rows(3)->columnSpanFull()
                    ->helperText(__('For the team only: agreements, fees, things to remember.')),
                Toggle::make('is_active')->label(__('Active'))->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable(['name', 'name_ja'])->sortable()->weight('bold')
                    ->description(fn (Tsk $r) => $r->name_ja),
                TextColumn::make('registration_number')->label(__('Registration number'))->searchable()->placeholder('—'),
                TextColumn::make('contact_person')->label(__('Contact person'))->placeholder('—')
                    ->description(fn (Tsk $r) => collect([$r->phone, $r->email])->filter()->implode(' · ') ?: null),
                TextColumn::make('companies_count')->counts('companies')->label(__('Companies'))->badge()->sortable(),
                TextColumn::make('vacancies_count')->counts('vacancies')->label(__('Jobs'))->badge()->color('info')->sortable(),
                IconColumn::make('is_active')->label(__('Active'))->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('is_active')->label(__('Active')),
                Filter::make('without_companies')->label(__('No companies yet'))
                    ->query(fn ($query) => $query->doesntHave('companies')),
            ])
            ->recordActions(DeleteActions::row(__('Its companies and their job postings stay, without a TSK.')))
            ->toolbarActions(DeleteActions::bulk(__('Their companies and job postings stay, without a TSK.')));
    }

    public static function getRelations(): array
    {
        return [RelationManagers\CompaniesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTsks::route('/'),
            'create' => Pages\CreateTsk::route('/create'),
            'edit'   => Pages\EditTsk::route('/{record}/edit'),
        ];
    }
}
