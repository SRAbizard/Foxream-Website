<?php

namespace App\Filament\Student\Pages;

use App\Models\Application;
use App\Support\AccountRemoval;
use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

/**
 * The candidate's account settings (name, email, password), plus deleting
 * the account: their right under the Personal Data Protection Law (UU
 * PDP). Everything goes for good: profile, CV, documents, applications
 * and files. Once proposed to a company (and on to departure) the team
 * must be involved, so the candidate is asked to contact it instead.
 */
class EditProfile extends BaseEditProfile
{
    /** Steps during which an account cannot be deleted from here. */
    public const IN_PROGRESS = ['proposed', 'interview', 'passed', 'contract', 'coe', 'visa', 'departure'];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deleteMyAccount')
                ->label(__('Delete my account'))
                ->icon('heroicon-o-trash')
                ->color('danger')->outlined()
                ->requiresConfirmation()
                ->modalHeading(__('Delete your account?'))
                ->modalDescription(__('Your profile, CV, documents, applications and uploaded files are deleted for good. This cannot be undone.'))
                ->modalSubmitActionLabel(__('Delete my account'))
                ->schema([
                    TextInput::make('password')->label(__('Your password'))
                        ->password()->revealable()->required()
                        ->currentPassword(guard: Filament::getAuthGuard()),
                ])
                ->action(function () {
                    $user = Filament::auth()->user();

                    if (Application::where('user_id', $user->id)->whereIn('status', self::IN_PROGRESS)->exists()) {
                        Notification::make()
                            ->title(__('You have been proposed to a company'))
                            ->body(__('Please contact our team to delete your account, so your application with the company can be closed properly.'))
                            ->warning()->persistent()->send();

                        return;
                    }

                    // Signed out first, so the account log does not point at the deleted account.
                    Filament::auth()->logout();
                    session()->invalidate();
                    session()->regenerateToken();

                    AccountRemoval::delete($user);

                    session()->flash('notice', __('Your account and all your data have been deleted.'));
                    $this->redirect(url('/'));
                }),
        ];
    }
}
