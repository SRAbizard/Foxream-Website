<?php

namespace App\Filament\Resources\DocumentTypes;

use App\Filament\Resources\DocumentTypes\Pages;
use App\Filament\Support\TranslatableTabs;
use App\Models\DocumentType;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class DocumentTypeResource extends Resource
{
    protected static ?string $model = DocumentType::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-duplicate';
    protected static string | UnitEnum | null $navigationGroup = 'Students';
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string { return __('Document Types'); }
    public static function getNavigationGroup(): ?string { return __('Students'); }
    public static function getModelLabel(): string      { return __('Document Type'); }
    public static function getPluralModelLabel(): string { return __('Document Types'); }

    public static function getGloballySearchableAttributes(): array
    {
        return ['key', 'label->id', 'label->en', 'label->ja'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->t('label');
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            __('Key')    => $record->key,
            __('Active') => $record->is_active ? __('Yes') : __('No'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->components([
                TextInput::make('key')
                    ->label(__('Key'))
                    ->helperText(__('Lowercase identifier used in code (e.g. ktp, paspor). Cannot be changed once students have uploaded documents of this type.'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(64)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set) => $set('key', Str::slug($state ?? '', '_'))),
                TextInput::make('icon')
                    ->label(__('Icon'))
                    ->default('heroicon-o-document')
                    ->helperText(__('Heroicon name, e.g. heroicon-o-identification')),
                TextInput::make('sort_order')->numeric()->default(0),
                Toggle::make('is_active')->label(__('Active'))->default(true)->inline(false)
                    ->helperText(__('Inactive types are hidden from upload dropdowns but kept for historical records.')),
                // A form the student downloads, fills in and uploads back (the MCU form).
                FileUpload::make('template_path')->label(__('Form for students to download'))
                    ->helperText(__('Optional. Students see a download link for this document type in My Documents.'))
                    ->disk('public')->directory('document-templates')
                    ->storeFileNamesIn('template_name')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(10240)
                    ->downloadable()
                    ->columnSpanFull(),
            ]),
            TranslatableTabs::for('label', TextInput::class, label: __('Label'), required: true),
            TranslatableTabs::for('description', Textarea::class, label: __('Description (optional)')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('sort_order')->label('#')->sortable(),
            TextColumn::make('key')->label(__('Key'))->copyable()->searchable()
                ->fontFamily('mono')->color('gray'),
            TextColumn::make('label')->label(__('Label'))
                ->getStateUsing(fn ($record) => $record->t('label'))
                ->searchable(query: function ($q, $search) {
                    $q->where('label->id', 'like', "%{$search}%")
                      ->orWhere('label->en', 'like', "%{$search}%")
                      ->orWhere('label->ja', 'like', "%{$search}%");
                })
                ->weight('bold')->wrap(),
            TextColumn::make('icon')->label(__('Icon'))->color('gray')->fontFamily('mono')->toggleable(),
            IconColumn::make('is_active')->boolean()->label(__('Active')),
        ])
        ->defaultSort('sort_order')
        ->reorderable('sort_order')
        ->filters([
            TernaryFilter::make('is_active')->label(__('Active'))->boolean(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDocumentTypes::route('/'),
            'create' => Pages\CreateDocumentType::route('/create'),
            'edit'   => Pages\EditDocumentType::route('/{record}/edit'),
        ];
    }
}
