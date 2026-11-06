<?php

namespace App\Filament\Resources\AccessLogs;

use App\Filament\Resources\AccessLogs\Pages;
use App\Filament\Resources\Students\Pages\ViewStudent;
use App\Filament\Support\ForStudentManagers;
use App\Models\AccessLog;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Access history, as in the learning platform: logins (also failed ones) and what
 * students do, with IP and device. Admins with "manage students" see the
 * students' lines; superadmins see everyone's.
 */
class AccessLogResource extends Resource
{
    use ForStudentManagers;

    protected static ?string $model = AccessLog::class;
    protected static ?string $slug = 'access-history';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-finger-print';
    protected static string | UnitEnum | null $navigationGroup = 'Students';
    protected static ?int $navigationSort = 9;

    public static function getNavigationLabel(): string  { return __('Access history'); }
    public static function getNavigationGroup(): ?string { return __('Students'); }
    public static function getModelLabel(): string       { return __('Access history'); }
    public static function getPluralModelLabel(): string { return __('Access history'); }

    public static function canCreate(): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user.roles')
            ->when(! auth()->user()?->hasRole('superadmin'), fn ($q) => $q->where(fn ($q) => $q
                ->whereNull('user_id')
                ->orWhereHas('user.roles', fn ($r) => $r->where('name', 'student'))));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label(__('Time'))->dateTime('d M Y H:i:s')->sortable()
                    ->color('gray')->size('sm'),
                TextColumn::make('user.name')->label(__('User'))->placeholder('—')->weight('bold')
                    ->description(fn (AccessLog $r) => $r->user?->email)
                    ->url(fn (AccessLog $r) => $r->user?->hasRole('student') ? ViewStudent::getUrl(['record' => $r->user_id]) : null)
                    ->searchable(['name', 'email']),
                TextColumn::make('action')->label(__('Activity'))->badge()
                    ->formatStateUsing(fn (AccessLog $r) => $r->label())
                    ->color(fn ($state) => in_array($state, AccessLog::WARNINGS, true) ? 'warning' : 'gray'),
                TextColumn::make('description')->label(__('Details'))->placeholder('—')->limit(50)->wrap()
                    ->tooltip(fn (AccessLog $r) => $r->description),
                TextColumn::make('ip_address')->label(__('IP / device'))
                    ->description(fn (AccessLog $r) => $r->device)
                    ->tooltip(fn (AccessLog $r) => $r->user_agent),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('action')->label(__('Activity'))->options(AccessLog::labels()),
                Filter::make('date')
                    ->schema([DatePicker::make('date')->label(__('Date'))->native(false)])
                    ->query(fn (Builder $query, array $data) => $query->when($data['date'] ?? null, fn ($q, $d) => $q->whereDate('created_at', $d)))
                    ->indicateUsing(fn (array $data) => ($data['date'] ?? null) ? __('Date').': '.\Illuminate\Support\Carbon::parse($data['date'])->isoFormat('ll') : null),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->poll('60s');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAccessLogs::route('/')];
    }
}
