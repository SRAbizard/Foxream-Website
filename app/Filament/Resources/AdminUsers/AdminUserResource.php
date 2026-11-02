<?php

namespace App\Filament\Resources\AdminUsers;

use App\Filament\Resources\AdminUsers\Pages;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Admin accounts, for superadmins: create admins, switch them off, and
 * choose who may see and handle students ("manage students").
 */
class AdminUserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $slug = 'admin-accounts';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';
    protected static string | UnitEnum | null $navigationGroup = 'CMS';
    protected static ?int $navigationSort = 90;

    public static function getNavigationLabel(): string  { return __('Admin accounts'); }
    public static function getNavigationGroup(): ?string { return __('CMS'); }
    public static function getModelLabel(): string       { return __('Admin account'); }
    public static function getPluralModelLabel(): string { return __('Admin accounts'); }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('roles', fn ($q) => $q->whereIn('name', ['superadmin', 'admin']));
    }

    public static function form(Schema $schema): Schema
    {
        // Superadmins cannot demote or switch off themselves.
        $self = fn (?User $record) => $record?->is(auth()->user()) ?? false;

        return $schema->components([
            Section::make()->columns(2)->components([
                TextInput::make('name')->label(__('Name'))->required()->maxLength(120),
                TextInput::make('email')->label(__('Email'))->email()->required()->maxLength(160)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')->label(__('Password'))->password()->revealable()
                    ->minLength(8)->maxLength(120)
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'edit' ? __('Leave empty to keep the current password.') : null),
                Select::make('role')->label(__('Role'))
                    ->options(['admin' => __('Admin'), 'superadmin' => __('Superadmin')])
                    ->default('admin')->required()->native(false)->live()
                    ->disabled($self),
                Toggle::make('manage_students')->label(__('Can see and manage students'))
                    ->helperText(__('Students, their CVs and documents, applications and every step up to departure. Superadmins always can.'))
                    ->visible(fn (Get $get) => $get('role') === 'admin'),
                Toggle::make('is_active')->label(__('Active'))->default(true)
                    ->helperText(__('Inactive accounts cannot log in.'))
                    ->disabled($self),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->weight('bold'),
                TextColumn::make('email')->label(__('Email'))->searchable(),
                TextColumn::make('role')->label(__('Role'))->badge()
                    ->getStateUsing(fn (User $record) => $record->hasRole('superadmin') ? __('Superadmin') : __('Admin'))
                    ->color(fn (User $record) => $record->hasRole('superadmin') ? 'primary' : 'gray'),
                IconColumn::make('manage_students')->label(__('Students'))->boolean()
                    ->getStateUsing(fn (User $record) => $record->canManageStudents()),
                IconColumn::make('is_active')->label(__('Active'))->boolean(),
                TextColumn::make('last_login_at')->label(__('Last login'))->since()->placeholder('—'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAdminUsers::route('/'),
            'create' => Pages\CreateAdminUser::route('/create'),
            'edit'   => Pages\EditAdminUser::route('/{record}/edit'),
        ];
    }
}
