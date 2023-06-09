<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Factory\Translation;

use PhpTuf\ComposerStager\Domain\Factory\Translation\TranslatableAwareTrait;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslationParametersInterface;

final class TestTranslationParameters implements TranslationParametersInterface
{
    use TranslatableAwareTrait;

    public function __construct(private readonly array $parameters = [])
    {
    }

    public function getAll(): array
    {
        return $this->parameters;
    }
}
