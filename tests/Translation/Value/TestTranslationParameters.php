<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Value;

use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;

final class TestTranslationParameters implements TranslationParametersInterface
{
    public function __construct(private readonly array $parameters = [])
    {
    }

    public function getAll(): array
    {
        return $this->parameters;
    }
}
