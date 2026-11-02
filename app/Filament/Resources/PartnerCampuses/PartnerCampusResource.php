<?php

namespace App\Filament\Resources\PartnerCampuses;

use App\Filament\Resources\PartnerCampuses\Pages;
use App\Filament\Support\ForStudentManagers;
use App\Models\PartnerCampus;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Campuses whose students may request the Internship visa target. */
class PartnerCampusResource extends Resource
{
    use ForStudentManagers;

    protected static ?string $model = PartnerCampus::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-building-library';
    protected static string | UnitEnum | null $navigationGroup = 'Students';
    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string  { return __('Partner campuses'); }
    public static function getNavigationGroup(): ?string { return __('Students'); }
    public static function getModelLabel(): string       { return __('Partner campus'); }
    public static function getPluralModelLabel(): string { return __('Partner campuses'); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->components([
                TextInput::make('name')->label(__('Campus name'))->required()->maxLength(160),
                TextInput::make('city')->label(__('City'))->maxLength(80),
                TextInput::make('sort_order')->label(__('Order'))->numeric()->default(0),
                Toggle::make('is_active')->label(__('Shown to students'))->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('Campus name'))->searchable()->weight('bold'),
                TextColumn::make('city')->label(__('City'))->searchable()->placeholder('—'),
                IconColumn::make('is_active')->label(__('Shown to students'))->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPartnerCampuses::route('/'),
            'create' => Pages\CreatePartnerCampus::route('/create'),
            'edit'   => Pages\EditPartnerCampus::route('/{record}/edit'),
        ];
    }
}
