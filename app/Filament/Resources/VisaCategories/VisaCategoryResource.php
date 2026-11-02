<?php

namespace App\Filament\Resources\VisaCategories;

use App\Filament\Resources\VisaCategories\Pages;
use App\Filament\Support\TranslatableTabs;
use App\Models\VisaCategory;
use BackedEnum;
use App\Models\DocumentType;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class VisaCategoryResource extends Resource
{
    protected static ?string $model = VisaCategory::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-identification';
    protected static string | UnitEnum | null $navigationGroup = 'Recruitment';
    protected static ?int $navigationSort = 12;

    public static function getNavigationLabel(): string { return __('Visa Categories'); }
    public static function getNavigationGroup(): ?string { return __('Recruitment'); }

    public static function getGloballySearchableAttributes(): array
    {
        return ['slug', 'name->id', 'name->en', 'name->ja'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->t('name');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->components([
                TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(80)
                    ->live(onBlur: true)->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('sort_order')->numeric()->default(0),
                ColorPicker::make('color')->default('#b32510'),
            ]),
            TranslatableTabs::for('name', TextInput::class, label: __('Name'), required: true),
            TranslatableTabs::for('description', Textarea::class, label: __('Description'),
                componentMods: ['rows' => [3]]),

            Section::make(__('Document requirements'))
                ->description(__('Required = must upload (counts toward progress). Optional = accepted but not blocking.'))
                ->components([
                    Select::make('required_documents')
                        ->label(__('Required documents'))
                        ->helperText(__('These count toward the student\'s completion percentage.'))
                        ->multiple()
                        ->searchable()
                        ->options(fn () => DocumentType::options())
                        ->columnSpanFull(),
                    Select::make('optional_documents')
                        ->label(__('Optional documents'))
                        ->helperText(__('Accepted if uploaded, but not required to complete the checklist.'))
                        ->multiple()
                        ->searchable()
                        ->options(fn () => DocumentType::options())
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('sort_order')->label('#'),
            TextColumn::make('name')->label(__('Name'))->getStateUsing(fn ($record) => $record->t('name'))->searchable(),
            TextColumn::make('slug')->copyable(),
            ColorColumn::make('color'),
            TextColumn::make('vacancies_count')->counts('vacancies')->label(__('Jobs')),
            TextColumn::make('required_documents')
                ->label(__('Required docs'))
                ->formatStateUsing(fn ($state) => is_array($state) ? count($state).' '.__('types') : '0')
                ->badge()->color('info')->alignCenter(),
        ])
        ->defaultSort('sort_order')
        ->filters([
            \Filament\Tables\Filters\Filter::make('without_jobs')->label(__('No job postings'))
                ->query(fn ($query) => $query->doesntHave('vacancies')),
        ])
        ->recordActions(\App\Filament\Support\DeleteActions::row(__('Job postings and students with this visa target lose it and must be set again. This cannot be undone.')))
        ->toolbarActions(\App\Filament\Support\DeleteActions::bulk(__('Job postings and students with these visa targets lose them and must be set again. This cannot be undone.')));
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVisaCategories::route('/'),
            'create' => Pages\CreateVisaCategory::route('/create'),
            'edit'   => Pages\EditVisaCategory::route('/{record}/edit'),
        ];
    }
}
