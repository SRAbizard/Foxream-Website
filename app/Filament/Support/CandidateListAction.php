<?php

namespace App\Filament\Support;

use App\Models\Application;
use App\Models\Company;
use App\Support\CandidateList;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;

/**
 * "Candidate list (Excel)": the company's candidates in Japanese, one
 * sheet per job, to send to the company. On a company row it lists that
 * company; elsewhere the admin picks one.
 */
class CandidateListAction
{
    public static function make(bool $pickCompany = false): Action
    {
        return Action::make('candidateList')
            ->label(__('Candidate list (Excel)'))
            ->icon('heroicon-o-table-cells')
            ->color('gray')
            ->modalHeading(__('Candidate list for the company'))
            ->modalDescription(__('In Japanese, one sheet per job, with the photo, our assessment and a link to each CV (valid for 60 days). Fill in the assessment on each application first.'))
            ->modalSubmitActionLabel(__('Download'))
            ->schema([
                ...($pickCompany ? [
                    Select::make('company_id')->label(__('Company'))->required()->searchable()
                        ->options(fn () => Company::whereHas('vacancies.applications')->orderBy('name')->pluck('name', 'id')),
                ] : []),
                CheckboxList::make('statuses')->label(__('Applications at these steps'))
                    ->helperText(__('Only applications at the ticked steps go on the list. E.g. tick only Screening with the Japan team to send the candidates the company will screen next.'))
                    ->options(collect(Application::STATUSES)->mapWithKeys(fn ($s) => [$s => __('application.status.'.$s)]))
                    ->default(CandidateList::DEFAULT_STATUSES)
                    ->columns(3)->required(),
            ])
            ->action(fn (array $data, ?Company $record) => CandidateList::download(
                $pickCompany ? Company::findOrFail($data['company_id']) : $record,
                $data['statuses'],
            ));
    }
}
