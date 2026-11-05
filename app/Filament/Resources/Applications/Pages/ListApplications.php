<?php
namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Support\CandidateListAction;
use App\Support\ExcelExport;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CandidateListAction::make(pickCompany: true),

            // The applications in the table, with the current search and filters.
            Action::make('export')->label(__('Download list (Excel)'))
                ->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(fn () => ExcelExport::download('applications', [
                    __('Applicant'), __('Email'), __('Phone'), __('Job'), __('TSK'), __('Company'), __('Job Category'), __('Visa'),
                    __('Step'), __('Next schedule'), __('Applied'), __('Departure'),
                ], $this->getFilteredSortedTableQuery()
                    ->with('user', 'vacancy.company.tsk', 'vacancy.jobCategory', 'vacancy.visaCategory', 'schedules')
                    ->get()
                    ->map(function ($a) {
                        $next = $a->schedules->first(fn ($s) => $s->isUpcoming());

                        return [
                            $a->user?->name, $a->user?->email, (string) $a->user?->phone,
                            $a->vacancy?->t('title'), $a->vacancy?->company?->tsk?->name, $a->vacancy?->company?->name,
                            $a->vacancy?->jobCategory?->t('name'), $a->vacancy?->visaCategory?->t('name'),
                            __('application.status.'.$a->status),
                            $next ? $next->type_label.' · '.($next->starts_at?->format('Y-m-d H:i') ?? __('To follow')) : null,
                            $a->created_at?->format('Y-m-d H:i'),
                            $a->departure_at?->format('Y-m-d H:i'),
                        ];
                    }))),
        ];
    }
}
