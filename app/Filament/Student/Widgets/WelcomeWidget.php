<?php

namespace App\Filament\Student\Widgets;

use App\Models\User;
use App\Support\CvCompleteness;
use Filament\Widgets\Widget;

class WelcomeWidget extends Widget
{
    protected string $view = 'filament.student.widgets.welcome-widget';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        return [
            'user'        => $user,
            'percent'     => CvCompleteness::for($user)['pct'],
            'verified'    => $user?->hasVerifiedEmail() ?? false,
            'profileUrl'  => \App\Filament\Student\Pages\MyProfile::getUrl(),
        ];
    }
}
