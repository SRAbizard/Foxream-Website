<?php

namespace App\Filament\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;
use SensitiveParameter;

/**
 * Student registration with an explicit consent to the Terms and the
 * Privacy Policy, as the personal data protection law requires before we
 * collect documents and profile data. Both documents open in a pop-up
 * (not a new page) with an "I agree" tick at the bottom; agreeing there
 * ticks the consent box.
 */
class Register extends BaseRegister
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getConsentFormComponent(),
        ]);
    }

    protected function getConsentFormComponent(): Component
    {
        $link = fn (string $doc, string $label) => '<button type="button" class="site-legal-link" wire:click.prevent.stop="mountAction(\'legal\', { doc: \''.$doc.'\' })">'.e($label).'</button>';

        return Checkbox::make('consent')
            ->label(new HtmlString(__('I agree to the :terms and the :privacy, including the processing of my personal data for job placement in Japan.', [
                'terms'   => $link('terms', __('Terms & Conditions')),
                'privacy' => $link('privacy', __('Privacy Policy')),
            ])))
            // Ticking the box opens the documents; it is ticked once the
            // visitor agrees there. Unticking works as usual.
            ->extraInputAttributes(['x-on:click' => "if (\$el.checked) { \$event.preventDefault(); \$wire.mountAction('legal') }"])
            ->accepted()
            ->validationMessages(['accepted' => __('Please agree to the Terms & Conditions and Privacy Policy to continue.')]);
    }

    public function legalAction(): Action
    {
        return Action::make('legal')
            ->modalHeading(__('Terms & Conditions and Privacy Policy'))
            ->modalDescription(__('Please read both documents, then tick the box at the bottom.'))
            ->modalWidth(Width::ThreeExtraLarge)
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->schema(fn (array $arguments): array => [
                View::make('filament.auth.legal-documents')
                    ->viewData(['doc' => ($arguments['doc'] ?? 'terms') === 'privacy' ? 'privacy' : 'terms']),
                Checkbox::make('agree')
                    ->label(__('I have read and agree to the Terms & Conditions and the Privacy Policy, including the processing of my personal data for job placement in Japan.'))
                    ->accepted()
                    ->validationMessages(['accepted' => __('Tick the box to agree.')]),
            ])
            ->modalSubmitActionLabel(__('I agree'))
            ->action(function (): void {
                $this->data['consent'] = true;
                $this->resetErrorBag('data.consent');
            });
    }

    protected function mutateFormDataBeforeRegister(#[SensitiveParameter] array $data): array
    {
        unset($data['consent']);

        return $data;
    }
}
