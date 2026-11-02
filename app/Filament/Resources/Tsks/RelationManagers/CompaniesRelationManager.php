<?php

namespace App\Filament\Resources\Tsks\RelationManagers;

use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** The companies a TSK supports: add existing ones, create one, or take one off. */
class CompaniesRelationManager extends RelationManager
{
    protected static string $relationship = 'companies';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Companies');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->weight('bold'),
                TextColumn::make('industry')->label(__('Industry'))->placeholder('—'),
                TextColumn::make('city')->label(__('City'))->placeholder('—'),
                TextColumn::make('vacancies_count')->counts('vacancies')->label(__('Jobs'))->badge(),
            ])
            ->recordUrl(fn (Company $record) => CompanyResource::getUrl('edit', ['record' => $record]))
            ->headerActions([
                AssociateAction::make()->label(__('Add company'))->multiple()->preloadRecordSelect()
                    ->modalDescription(__('A company that already has another TSK moves to this one.')),
                Action::make('newCompany')->label(__('New company'))->icon('heroicon-o-building-office-2')->color('gray')
                    ->url(fn () => CompanyResource::getUrl('create', ['tsk' => $this->getOwnerRecord()->id])),
            ])
            ->recordActions([
                DissociateAction::make()->label(__('Remove from this TSK')),
            ])
            ->toolbarActions([
                DissociateBulkAction::make()->label(__('Remove from this TSK')),
            ])
            ->emptyStateHeading(__('No companies yet'));
    }
}
