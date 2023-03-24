<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Translation;

use Stringable;

/**
 * Defines an interface for translation.
 *
 * @api
 */
interface TranslationInterface
{
    /**
     * Translates a string.
     *
     * @param string $string
     *   Source string
     *
     * @return string|\Stringable
     *   Translated string.
     */
    public function translate(string $string): string|Stringable;
}
