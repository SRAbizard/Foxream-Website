<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\JobCategory;
use App\Support\CvCompleteness;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // The students in the table, with the current search and filters, as an Excel file.
            Action::make('export')->label(__('Download list (Excel)'))
                ->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(function () {
                    $users = $this->getFilteredSortedTableQuery()
                        ->with('studentProfile.primaryVisa', 'educations', 'applications')
                        ->get();
                    $fields = JobCategory::all()->keyBy('slug');

                    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'students-'.bin2hex(random_bytes(6)).'.xlsx';
                    $writer = new Writer();
                    $writer->openToFile($path);
                    $writer->addRow(Row::fromValues([
                        __('Name'), __('Full legal name'), __('Email'), __('Email verified'), __('Phone'),
                        __('City'), __('Province'), __('Position applied for'), __('Visa target'), __('Visa target status'),
                        __('CV'), __('Applications'), __('Registered'), __('Last login'), __('Active'),
                    ], (new Style())->setFontBold()));
                    foreach ($users as $u) {
                        $p = $u->studentProfile;
                        $writer->addRow(Row::fromValues([
                            $u->name, $p?->full_name, $u->email,
                            $u->email_verified_at?->format('Y-m-d') ?? '',
                            (string) $u->phone, $p?->city, $p?->province,
                            $fields->get($p?->desired_job)?->t('name') ?? $p?->desired_job,
                            $p?->primaryVisa?->t('name'),
                            $p?->visa_target_status ? __('visa.target.status.'.$p->visa_target_status) : '',
                            CvCompleteness::for($u)['pct'].'%',
                            $u->applications->count(),
                            $u->created_at?->format('Y-m-d H:i'),
                            $u->last_login_at?->format('Y-m-d H:i') ?? '',
                            $u->is_active === false ? __('No') : __('Yes'),
                        ]));
                    }
                    $writer->close();

                    return response()->download($path, 'students-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
                }),
        ];
    }
}
