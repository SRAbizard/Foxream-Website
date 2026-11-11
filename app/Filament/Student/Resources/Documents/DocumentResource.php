<?php

namespace App\Filament\Student\Resources\Documents;

use App\Filament\Student\Resources\Documents\Pages;
use App\Models\DocumentType;
use App\Models\StudentDocument;
use App\Support\StudentDocumentProgress;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class DocumentResource extends Resource
{
    protected static ?string $model = StudentDocument::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-arrow-up';
    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string { return __('My Documents'); }
    public static function getModelLabel(): string      { return __('Document'); }
    public static function getPluralModelLabel(): string { return __('Documents'); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->components([
                Select::make('type')->label(__('Document type'))
                    ->options(function () {
                        // Only show document types that are required or optional
                        // for THIS student's visa target — anything else is noise.
                        $progress = StudentDocumentProgress::for(auth()->user());
                        $allowed  = array_unique(array_merge($progress['required'], $progress['optional']));

                        if (empty($allowed)) return [];

                        return DocumentType::query()
                            ->whereIn('key', $allowed)
                            ->active()
                            ->ordered()
                            ->get()
                            ->mapWithKeys(fn ($t) => [$t->key => $t->t('label')])
                            ->all();
                    })
                    ->helperText(function (?string $state) {
                        $source = StudentDocumentProgress::targetSource(auth()->user());
                        $text = match ($source) {
                            'confirmed'    => __('Showing only documents required or optional for your confirmed visa target.'),
                            'applications' => __('Showing documents inferred from your job applications.'),
                            default        => __('Showing the default document set. Confirm a visa target for a tailored list.'),
                        };

                        // A type with its own form (the MCU): link to download it.
                        $type = $state ? DocumentType::where('key', $state)->whereNotNull('template_path')->first() : null;
                        if (! $type) {
                            return $text;
                        }

                        return new HtmlString(e($text).'<br><a href="'.e($type->templateUrl()).'" download="'.e($type->template_name).'" class="font-semibold text-primary-600 underline dark:text-primary-400">'
                            .e(__('Download the :document form, fill it in, then upload it here.', ['document' => $type->t('label')])).'</a>');
                    })
                    ->live()
                    ->required()->native(false)->searchable(),
                TextInput::make('label')->label(__('Custom label'))->maxLength(160)
                    ->placeholder(__('Optional label or note')),
                FileUpload::make('file_path')->label(__('File'))
                    ->required()
                    ->disk(\App\Support\PrivateFiles::DISK)->visibility('private')->directory('students/documents')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(5120) // 5MB
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('type')->label(__('Type'))->badge()
                ->formatStateUsing(fn ($state) => DocumentType::labelFor($state)),
            TextColumn::make('label')->label(__('Label'))->limit(40),
            TextColumn::make('status')->label(__('Status'))->badge()
                ->formatStateUsing(fn ($state) => __('document.status.'.$state))
                ->color(fn ($state) => StudentDocument::STATUS_COLORS[$state] ?? 'gray'),
            TextColumn::make('verified_at')->dateTime('d M Y')->label(__('Verified at'))->placeholder('—')->toggleable(),
            TextColumn::make('notes')->label(__('Admin notes'))->placeholder('—')->limit(40)->wrap()->toggleable(),
            TextColumn::make('created_at')->dateTime('d M Y')->label(__('Uploaded'))->sortable(),
        ])
        ->defaultSort('created_at', 'desc')
        ->filters([
            SelectFilter::make('type')
                ->options(fn () => DocumentType::options(activeOnly: false)),
            SelectFilter::make('status')
                ->options(collect(StudentDocument::STATUSES)->mapWithKeys(fn ($s) => [$s => __('document.status.'.$s)])),
        ])
        ->recordActions([
            Action::make('view')
                ->label(__('View'))
                ->icon('heroicon-o-eye')
                ->url(fn (StudentDocument $r) => $r->file_url)
                ->openUrlInNewTab(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status']  = 'pending';
        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit'   => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
