<?php
namespace App\Filament\Resources\Tsks\Pages;

use App\Filament\Resources\Tsks\TskResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTsk extends EditRecord
{
    protected static string $resource = TskResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
