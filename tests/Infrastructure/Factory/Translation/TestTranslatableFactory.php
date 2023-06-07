<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Factory\Translation;

use PhpTuf\ComposerStager\Domain\Factory\Translation\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslatableInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslationParametersInterface;
use PhpTuf\ComposerStager\Infrastructure\Value\Translation\TranslationParameters;
use PhpTuf\ComposerStager\Tests\Infrastructure\Value\Translation\TestTranslatableMessage;

final class TestTranslatableFactory implements TranslatableFactoryInterface
{
    public function createTranslatableMessage(
        string $message,
        ?TranslationParametersInterface $parameters = null,
        ?string $domain = null,
    ): TranslatableInterface {
        return new TestTranslatableMessage($message, $parameters, $domain);
    }

    public function createTranslationParameters(array $parameters = []): TranslationParametersInterface
    {
        return new TranslationParameters($parameters);
    }
}
