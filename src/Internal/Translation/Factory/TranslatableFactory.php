<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Translation\Factory;

use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters;

/**
 * Creates translatable objects.
 *
 * @package Translation
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
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
