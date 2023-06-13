<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Translation\Factory;

use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Domain\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslatableMessage;
use PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslationParameters;

/**
 * Creates translatable objects.
 *
 * @package Translation
 *
 * @api
 */
final class TranslatableFactory implements TranslatableFactoryInterface
{
    public function createTranslatableMessage(
        string $message,
        ?TranslationParametersInterface $parameters = null,
        ?string $domain = null,
    ): TranslatableInterface {
        return new TranslatableMessage($message, $parameters, $domain);
    }

    public function createTranslationParameters(array $parameters = []): TranslationParametersInterface
    {
        return new TranslationParameters($parameters);
    }
}
