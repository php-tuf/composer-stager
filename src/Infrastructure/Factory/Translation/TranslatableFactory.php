<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Factory\Translation;

use PhpTuf\ComposerStager\Domain\Factory\Translation\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslatableInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslationParametersInterface;
use PhpTuf\ComposerStager\Infrastructure\Value\Translation\TranslatableMessage;
use PhpTuf\ComposerStager\Infrastructure\Value\Translation\TranslationParameters;

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
