<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Domain\Translation;

use PhpTuf\ComposerStager\Domain\Service\Translation\TranslationInterface;
use Stringable;

/**
 * Defines a class for a pass-through translation service.
 */
final class PassthroughTranslation implements TranslationInterface
{
    public function translate(string $string): string|Stringable
    {
        return $string;
    }
}
