<?php

namespace App\Filament\Resources\StudentDocuments;

use App\Filament\Resources\StudentDocuments\Pages;
use App\Models\DocumentType;
use App\Models\StudentDocument;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Filament\Support\ForStudentManagers;
use UnitEnum;

class StudentDocumentResource extends Resource
{
    use ForStudentManagers;

    protected static ?string $model = StudentDocument::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-check';
    protected static string | UnitEnum | null $navigationGroup = 'Students';
    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string { return __('Student Documents'); }
    public static function getNavigationGroup(): ?string { return __('Students'); }
    public static function getModelLabel(): string      { return __('Student Document'); }
    public static function getPluralModelLabel(): string { return __('Student Documents'); }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string { return 'warning'; }

    public static function canCreate(): bool { return false; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Document'))->columns(2)->components([
                TextInput::make('user.name')->label(__('Student'))->disabled(),
                TextInput::make('type')
                    ->label(__('Type'))
                    ->formatStateUsing(fn ($state) => DocumentType::labelFor($state))
                    ->disabled(),
                TextInput::make('label')->label(__('Label'))->disabled(),
                FileUpload::make('file_path')->label(__('File'))->disk(\App\Support\PrivateFiles::DISK)->visibility('private')->disabled()->columnSpanFull(),
            ]),
            Section::make(__('Verification'))->columns(2)->components([
                Select::make('status')->label(__('Status'))
                    ->options(collect(StudentDocument::STATUSES)
                        ->mapWithKeys(fn ($s) => [$s => __('document.status.'.$s)]))
                    ->required()->native(false),
                Textarea::make('notes')->label(__('Admin notes'))->rows(3)
                    ->placeholder(__('Add a reason or comment for the student.'))
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('user.name')->label(__('Student'))->searchable()->weight('bold'),
            TextColumn::make('type')->label(__('Type'))->badge()
                ->formatStateUsing(fn ($state) => DocumentType::labelFor($state)),
            TextColumn::make('label')->label(__('Label'))->limit(40)->placeholder('—')->toggleable(),
            TextColumn::make('status')->label(__('Status'))->badge()
                ->formatStateUsing(fn ($state) => __('document.status.'.$state))
                ->color(fn ($state) => StudentDocument::STATUS_COLORS[$state] ?? 'gray'),
            TextColumn::make('verified_at')->dateTime('d M Y')->placeholder('—')->toggleable(),
            TextColumn::make('verifier.name')->label(__('Verified by'))->placeholder('—')->toggleable(),
            TextColumn::make('created_at')->dateTime('d M Y H:i')->label(__('Uploaded'))->sortable(),
        ])
        ->defaultSort('created_at', 'desc')
        ->filters([
            SelectFilter::make('status')
                ->options(collect(StudentDocument::STATUSES)
                    ->mapWithKeys(fn ($s) => [$s => __('document.status.'.$s)]))
                ->default('pending'),
            SelectFilter::make('type')
                ->options(fn () => DocumentType::options(activeOnly: false)),
        ])
        ->recordActions([
            Action::make('view')
                ->label(__('View file'))
                ->icon('heroicon-o-eye')
                ->url(fn (StudentDocument $r) => $r->file_url)
                ->openUrlInNewTab(),
            Action::make('verify')
                ->label(__('Verify'))
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (StudentDocument $r) => $r->status !== 'verified')
                ->requiresConfirmation()
                ->action(fn (StudentDocument $r) => $r->update([
                    'status'      => 'verified',
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                ])),
            Action::make('reject')
                ->label(__('Reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (StudentDocument $r) => $r->status !== 'rejected')
                ->form([Textarea::make('notes')->label(__('Reason'))->required()->rows(3)])
                ->action(fn (StudentDocument $r, array $data) => $r->update([
                    'status'      => 'rejected',
                    'notes'       => $data['notes'],
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                ])),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentDocuments::route('/'),
            'edit'  => Pages\EditStudentDocument::route('/{record}/edit'),
        ];
    }
}
