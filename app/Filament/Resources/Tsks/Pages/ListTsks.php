<?php
namespace App\Filament\Resources\Tsks\Pages;

use App\Filament\Resources\Tsks\TskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTsks extends ListRecords
{
    protected static string $resource = TskResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
