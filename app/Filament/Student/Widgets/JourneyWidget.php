<?php

namespace App\Filament\Student\Widgets;

use App\Support\StudentJourney;
use Filament\Widgets\Widget;

/** The eleven steps to Japan, with the current one and what to do next. */
class JourneyWidget extends Widget
{
    protected string $view = 'filament.student.widgets.journey';
    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return ['steps' => StudentJourney::for(auth()->user())];
    }
}
