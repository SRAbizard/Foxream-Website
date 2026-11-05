<?php

namespace App\Support;

use Illuminate\Translation\Translator;

/** Every translation can write the brand as :brand, :company and :learning. */
class BrandTranslator extends Translator
{
    protected function makeReplacements($line, array $replace)
    {
        return parent::makeReplacements($line, str_contains((string) $line, ':') ? $replace + Brand::replacements() : $replace);
    }
}
