<?php
namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    /** "New company" from a TSK's page comes with that TSK picked. */
    protected function afterFill(): void
    {
        if ($tsk = request()->integer('tsk')) {
            $this->data['tsk_id'] = $tsk;
        }
    }
}
