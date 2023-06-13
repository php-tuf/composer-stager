<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Factory;

use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;

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
