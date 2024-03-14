<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Service\DomainOptionsInterface;
use PhpTuf\ComposerStager\API\Translation\Service\LocaleOptionsInterface;
use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxyInterface;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper as Helper;

/**
 * Provides convenience methods for TranslationTestHelper calls.
 *
 * @see \PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper
 */
trait TranslationTestHelperTrait
{
    protected static function createDomainOptions(): DomainOptionsInterface
    {
        return Helper::createDomainOptions();
    }

    protected static function createLocaleOptions(): LocaleOptionsInterface
    {
        return Helper::createLocaleOptions();
    }

    protected static function createSymfonyTranslatorProxy(): SymfonyTranslatorProxyInterface
    {
        return Helper::createSymfonyTranslatorProxy();
    }

    protected static function createTranslatableFactory(): TranslatableFactoryInterface
    {
        return Helper::createTranslatableFactory();
    }

    protected static function createTranslatableExceptionMessage(
        string $message,
        ?TranslationParametersInterface $parameters = null,
    ): TranslatableInterface {
        return Helper::createTranslatableExceptionMessage($message, $parameters);
    }

    protected static function createTranslatableMessage(
        string $message,
        ?TranslationParametersInterface $parameters = null,
        ?string $domain = null,
    ): TranslatableInterface {
        return Helper::createTranslatableMessage($message, $parameters, $domain);
    }

    protected static function createTranslationParameters(array $parameters = []): TranslationParametersInterface
    {
        return Helper::createTranslationParameters($parameters);
    }

    protected static function createTranslator(): TranslatorInterface
    {
        return Helper::createTranslator();
    }
}
