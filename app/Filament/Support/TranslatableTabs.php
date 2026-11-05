<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * Build a Filament Section that wraps a Tabs component with one tab per supported
 * locale. Each tab holds the given input bound to `field.{locale}`, the Section
 * header makes the field name (Title / Content / etc.) visible to admins.
 *
 *   TranslatableTabs::for('title', TextInput::class, label: 'Title', required: true)
 */
class TranslatableTabs
{
    public static function for(
        string $field,
        string $component = TextInput::class,
        ?string $label = null,
        bool $required = false,
        bool $requiredOnDefaultLocaleOnly = true,
        ?string $description = null,
        array $componentMods = [],
    ): Section {
        $locales = array_keys(config('site.locales', []));
        $defaultLocale = config('site.default_locale', 'id');
        $label ??= str($field)->replace('_', ' ')->title()->toString();

        $tabs = collect($locales)->map(function (string $locale) use (
            $field, $component, $required, $requiredOnDefaultLocaleOnly,
            $defaultLocale, $componentMods,
        ) {
            $meta = config("site.locales.{$locale}");

            // Native names live in config but should still pass through the
            // translator so JP user sees "インドネシア語" instead of "Indonesia"
            // (and vice versa). __() falls back to the original string when no
            // translation key exists, so this is safe for any new locale added.
            $nativeName = __($meta['native'] ?? strtoupper($locale));

            /** @var Field $input */
            $input = $component::make("{$field}.{$locale}")
                ->hiddenLabel()
                ->placeholder($nativeName);

            $shouldBeRequired = $required && (
                ! $requiredOnDefaultLocaleOnly || $locale === $defaultLocale
            );
            if ($shouldBeRequired) {
                $input->required();
            }

            if ($component === TextInput::class) {
                $input->maxLength(191);
            } elseif ($component === Textarea::class) {
                $input->rows(4);
            } elseif ($component === RichEditor::class) {
                $input->toolbarButtons(['bold','italic','link','bulletList','orderedList','blockquote','h2','h3','undo','redo']);
            }

            foreach ($componentMods as $method => $args) {
                $input = $input->{$method}(...(array) $args);
            }

            $tabLabel = strtoupper($locale).' · '.$nativeName;
            if ($shouldBeRequired) {
                $tabLabel .= ' *';
            }

            return Tab::make($tabLabel)
                ->schema([$input]);
        })->all();

        $section = Section::make($label . ($required ? ' *' : ''))
            ->components([
                Tabs::make($field)->tabs($tabs)->columnSpanFull(),
            ])
            ->columnSpanFull();

        if ($description) {
            $section->description($description);
        } elseif ($required && $requiredOnDefaultLocaleOnly) {
            $defaultMeta = config("site.locales.{$defaultLocale}");
            $section->description(__('Required in :default. :other are optional.', [
                'default' => __($defaultMeta['native'] ?? strtoupper($defaultLocale)),
                'other'   => collect($locales)
                    ->reject(fn ($l) => $l === $defaultLocale)
                    ->map(fn ($l) => __(config("site.locales.{$l}.native") ?? strtoupper($l)))
                    ->join(' & '),
            ]));
        }

        return $section;
    }
}
