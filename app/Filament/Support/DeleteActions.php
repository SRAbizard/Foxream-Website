<?php

namespace App\Filament\Support;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;

/**
 * Edit and delete on each row, and delete for the ticked rows, for the
 * admin lists. The warning says what goes with the record.
 */
class DeleteActions
{
    public static function row(?string $warning = null, ?\Closure $visible = null): array
    {
        return [
            ActionGroup::make([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription($warning)
                    ->visible($visible ?? fn () => true),
            ]),
        ];
    }

    public static function bulk(?string $warning = null, ?\Closure $visible = null): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->modalDescription($warning)
                    ->visible($visible ?? fn () => true),
            ]),
        ];
    }
}
