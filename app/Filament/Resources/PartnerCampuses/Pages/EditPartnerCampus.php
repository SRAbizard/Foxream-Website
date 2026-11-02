<?php
namespace App\Filament\Resources\PartnerCampuses\Pages;

use App\Filament\Resources\PartnerCampuses\PartnerCampusResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPartnerCampus extends EditRecord
{
    protected static string $resource = PartnerCampusResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
