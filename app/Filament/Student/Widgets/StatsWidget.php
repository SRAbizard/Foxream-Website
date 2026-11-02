<?php

namespace App\Filament\Student\Widgets;

use App\Models\Application;
use App\Models\StudentDocument;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsWidget extends BaseWidget
{
    protected ?string $heading = null;

    protected function getStats(): array
    {
        $userId = auth()->id();

        $apps      = Application::where('user_id', $userId)->count();
        $appsActive = Application::where('user_id', $userId)
            ->whereNotIn('status', ['rejected', 'withdrawn'])->count();

        $interviews = Application::where('user_id', $userId)
            ->where('status', 'interview_scheduled')->count();

        $docsPending  = StudentDocument::where('user_id', $userId)->where('status', 'pending')->count();
        $docsVerified = StudentDocument::where('user_id', $userId)->where('status', 'verified')->count();

        $profilePct = \App\Support\CvCompleteness::for(auth()->user())['pct'];

        return [
            Stat::make(__('Applications'), $apps)
                ->description($appsActive > 0
                    ? __(':count active', ['count' => $appsActive])
                    : __('None active'))
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color($appsActive > 0 ? 'info' : 'gray')
                ->url(\App\Filament\Student\Pages\MyApplications::getUrl()),

            Stat::make(__('Interviews scheduled'), $interviews)
                ->description($interviews > 0 ? __('Get ready for your mensetsu!') : __('None yet'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($interviews > 0 ? 'success' : 'gray')
                ->url(\App\Filament\Student\Pages\MyApplications::getUrl()),

            Stat::make(__('Documents'), $docsVerified.' / '.($docsVerified + $docsPending))
                ->description($docsPending > 0
                    ? __(':count pending verification', ['count' => $docsPending])
                    : __('All verified'))
                ->descriptionIcon('heroicon-m-document-check')
                ->color($docsPending > 0 ? 'warning' : 'success')
                ->url(\App\Filament\Student\Resources\Documents\DocumentResource::getUrl('index')),

            Stat::make(__('CV'), $profilePct.'%')
                ->description($profilePct < 100 ? __('Complete it so companies can review you') : __('Complete'))
                ->descriptionIcon('heroicon-m-user-circle')
                ->color($profilePct < 100 ? 'warning' : 'success')
                ->url(\App\Filament\Student\Pages\MyProfile::getUrl()),
        ];
    }
}
