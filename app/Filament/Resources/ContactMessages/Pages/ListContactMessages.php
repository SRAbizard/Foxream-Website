<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('read_at')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope-open')
                    ->trueColor('gray')
                    ->falseIcon('heroicon-s-envelope')
                    ->falseColor('primary')
                    ->tooltip(fn ($state) => $state ? __('Read') : __('Unread')),
                TextColumn::make('name')
                    ->label(__('From'))
                    ->searchable()
                    ->weight(fn ($record) => $record->isUnread() ? 'bold' : 'normal'),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('subject')
                    ->searchable()
                    ->limit(50)
                    ->weight(fn ($record) => $record->isUnread() ? 'bold' : 'normal'),
                TextColumn::make('locale')
                    ->label(__('Lang'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('Received'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('unread')
                    ->label(__('Unread only'))
                    ->query(fn ($query) => $query->whereNull('read_at')),
                SelectFilter::make('locale')
                    ->options(collect(config('site.locales'))->map(fn ($m) => $m['native'])->toArray()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
