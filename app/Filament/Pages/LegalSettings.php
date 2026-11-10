<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Legal;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use UnitEnum;

/**
 * Admin → Legal: privacy policy, terms and the anti-fraud notice.
 * Editors open prefilled with the bundled
 * defaults so there is always a complete text to start from.
 */
class LegalSettings extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-scale';
    protected static string | UnitEnum | null $navigationGroup = 'CMS';
    protected static ?int $navigationSort = 91;

    protected string $view = 'filament.pages.site-settings';

    public ?array $data = [];

    private const LANGS = ['id' => 'Indonesia', 'en' => 'English', 'ja' => '日本語'];

    public static function getNavigationLabel(): string { return __('Legal'); }
    public static function getNavigationGroup(): ?string { return __('CMS'); }
    public function getTitle(): string { return __('Legal'); }

    public function mount(): void
    {
        $data = [];
        foreach (['privacy', 'terms'] as $doc) {
            $stored = (array) Setting::get("legal.{$doc}", []);
            foreach (array_keys(self::LANGS) as $lang) {
                $data["{$doc}_{$lang}"] = filled(strip_tags((string) ($stored[$lang] ?? '')))
                    ? $stored[$lang]
                    : Legal::defaultDocument($doc, $lang);
            }
        }

        $fraud = (array) Setting::get('legal.fraud_points', []);
        foreach (array_keys(self::LANGS) as $lang) {
            $data["fraud_{$lang}"]     = filled($fraud[$lang] ?? null) ? $fraud[$lang] : implode("\n", Legal::defaultFraudPoints($lang));
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('legal')->columnSpanFull()->tabs([
                Tab::make(__('Privacy Policy'))->icon('heroicon-o-lock-closed')->schema([
                    $this->documentEditors('privacy', __('Shown at /privacy-policy. Review it with your legal adviser; the starting text follows the Indonesian Personal Data Protection Law (UU PDP).')),
                ]),
                Tab::make(__('Terms & Conditions'))->icon('heroicon-o-document-text')->schema([
                    $this->documentEditors('terms', __('Shown at /terms and linked from the registration form.')),
                ]),
                Tab::make(__('Anti-fraud notice'))->icon('heroicon-o-shield-exclamation')->schema([
                    Section::make()
                        ->description(__('One warning per line. Shown on the Contact page and next to every job, together with your official channels from Website Settings → Contact. Make sure every line matches company policy.'))
                        ->columns(3)
                        ->components(collect(self::LANGS)->map(fn ($label, $lang) =>
                            Textarea::make("fraud_{$lang}")->label($label)->rows(9)->required($lang === 'id')
                        )->values()->all()),
                ]),
            ]),
        ])->statePath('data');
    }

    private function documentEditors(string $doc, string $hint): Tabs
    {
        return Tabs::make("{$doc}-langs")->tabs(
            collect(self::LANGS)->map(fn ($label, $lang) => Tab::make($label)->schema([
                RichEditor::make("{$doc}_{$lang}")->label(strtoupper($lang))->hiddenLabel()->helperText($lang === 'id' ? $hint : null)
                    ->required($lang === 'id')
                    ->toolbarButtons([['h2', 'h3', 'bold', 'italic', 'link'], ['bulletList', 'orderedList'], ['undo', 'redo']]),
            ]))->values()->all()
        );
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label(__('Save'))->color('primary')->submit('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $langs = array_keys(self::LANGS);

        foreach (['privacy', 'terms'] as $doc) {
            $new = collect($langs)->mapWithKeys(fn ($l) => [$l => (string) ($state["{$doc}_{$l}"] ?? '')])->all();
            if ($new != (array) Setting::get("legal.{$doc}", [])) {
                Setting::set("legal.{$doc}", $new, 'legal');
                Setting::set("legal.{$doc}_updated_at", now()->toDateString(), 'legal');
            }
        }

        Setting::set('legal.fraud_points', collect($langs)->mapWithKeys(fn ($l) => [$l => trim((string) ($state["fraud_{$l}"] ?? ''))])->all(), 'legal');

        Notification::make()->title(__('Settings saved'))->success()->send();
    }
}
