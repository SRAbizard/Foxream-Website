<?php
namespace App\Filament\Resources\PartnerCampuses\Pages;

use App\Filament\Resources\PartnerCampuses\PartnerCampusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPartnerCampuses extends ListRecords
{
    protected static string $resource = PartnerCampusResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
