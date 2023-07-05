<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Translation\Factory;

use PhpTuf\ComposerStager\API\Translation\Service\DomainOptionsInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestDomainOptions;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;

final class TestTranslatableFactory implements TranslatableFactoryInterface
{
    public function createDomainOptions(): DomainOptionsInterface
    {
        return new TestDomainOptions();
    }

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
