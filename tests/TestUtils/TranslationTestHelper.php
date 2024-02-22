<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Service\DomainOptionsInterface;
use PhpTuf\ComposerStager\API\Translation\Service\LocaleOptionsInterface;
use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactory;
use PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions;
use PhpTuf\ComposerStager\Internal\Translation\Service\LocaleOptions;
use PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxy;
use PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxyInterface;
use PhpTuf\ComposerStager\Internal\Translation\Service\Translator;

final class TranslationTestHelper
{
    public const DOMAIN_DEFAULT = 'messages';
    public const DOMAIN_EXCEPTIONS = 'exceptions';
    public const LOCALE_DEFAULT = 'en_US';

    public static function createDomainOptions(): DomainOptionsInterface
    {
        return new DomainOptions();
    }

    public static function createLocaleOptions(): LocaleOptionsInterface
    {
        return new LocaleOptions();
    }

    public static function createSymfonyTranslatorProxy(): SymfonyTranslatorProxyInterface
    {
        return new SymfonyTranslatorProxy();
    }

    public static function createTranslatableFactory(): TranslatableFactoryInterface
    {
        $translator = self::createTranslator();
        $domainOptions = self::createDomainOptions();

        return new TranslatableFactory($domainOptions, $translator);
    }

    public static function createTranslatableExceptionMessage(
        string $message,
        ?TranslationParametersInterface $parameters = null,
    ): TranslatableInterface {
        return self::createTranslatableMessage($message, $parameters, self::DOMAIN_EXCEPTIONS);
    }

    public static function createTranslatableMessage(
        string $message,
        ?TranslationParametersInterface $parameters = null,
        ?string $domain = null,
    ): TranslatableInterface {
        return self::createTranslatableFactory()->createTranslatableMessage($message, $parameters, $domain);
    }

    public static function createTranslationParameters(array $parameters = []): TranslationParametersInterface
    {
        return self::createTranslatableFactory()->createTranslationParameters($parameters);
    }

    public static function createTranslator(): TranslatorInterface
    {
        return new Translator(
            self::createDomainOptions(),
            self::createLocaleOptions(),
            self::createSymfonyTranslatorProxy(),
        );
    }
}
