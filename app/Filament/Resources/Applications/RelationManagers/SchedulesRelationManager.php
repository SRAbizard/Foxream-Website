<?php

namespace App\Filament\Resources\Applications\RelationManagers;

use App\Models\Application;
use App\Models\ApplicationSchedule;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Screenings (Indonesia team, Japan team — as many as needed), the job
 * interview and kaiwa practice for one application. Date and meeting
 * link may be left empty and added later; the student is notified.
 */
class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Schedules');
    }

    protected static function getModelLabel(): ?string       { return __('Schedule'); }
    protected static function getPluralModelLabel(): ?string { return __('Schedules'); }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('type')->label(__('Type'))
                ->options(collect(ApplicationSchedule::TYPES)->mapWithKeys(fn ($t) => [$t => __('schedule.type.'.$t)]))
                ->default(fn () => Application::STAGE_SCHEDULE[$this->getOwnerRecord()->status] ?? 'interview')
                ->required()->native(false),
            DateTimePicker::make('starts_at')->label(__('Date & time (WIB)'))
                ->seconds(false)->native(false)->displayFormat('d M Y H:i')
                ->helperText(__('Leave empty if the date is not known yet.')),
            TextInput::make('meeting_url')->label(__('Meeting link (Zoom)'))->url()->maxLength(500)
                ->placeholder('https://zoom.us/j/...')
                ->helperText(__('Can be added later.'))
                ->columnSpanFull(),
            Textarea::make('notes')->label(__('Notes for the student'))->rows(3)->maxLength(2000)
                ->placeholder(__('What to prepare, dress code, meeting ID and passcode…'))
                ->columnSpanFull(),
            Select::make('result')->label(__('Result'))
                ->options(collect(ApplicationSchedule::RESULTS)->mapWithKeys(fn ($r) => [$r => __('schedule.result.'.$r)]))
                ->placeholder(__('Not yet'))->native(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->label(__('Type'))->badge()
                    ->formatStateUsing(fn ($state) => __('schedule.type.'.$state)),
                TextColumn::make('starts_at')->label(__('Date & time (WIB)'))
                    ->dateTime('D, d M Y H:i')->placeholder(__('To follow')),
                TextColumn::make('meeting_url')->label(__('Meeting link'))
                    ->formatStateUsing(fn () => __('Open'))->url(fn ($record) => $record->meeting_url, true)
                    ->placeholder(__('To follow')),
                TextColumn::make('result')->label(__('Result'))->badge()
                    ->formatStateUsing(fn ($state) => __('schedule.result.'.$state))
                    ->color(fn ($state) => $state === 'passed' ? 'success' : 'danger')
                    ->placeholder('—'),
            ])
            ->headerActions([CreateAction::make()->label(__('Add schedule'))])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->emptyStateHeading(__('No schedules yet'))
            ->emptyStateDescription(__('Add the screening, interview or kaiwa practice. Date and link can follow.'));
    }
}
