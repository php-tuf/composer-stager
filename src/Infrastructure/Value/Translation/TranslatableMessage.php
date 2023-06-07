<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Value\Translation;

use PhpTuf\ComposerStager\Domain\Service\Translation\TranslatorInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslatableInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslationParametersInterface;

/**
 * Handles a translatable message.
 *
 * @see \PhpTuf\ComposerStager\Domain\Factory\Translation\TranslatableFactoryInterface
 * @see https://symfony.com/doc/current/translation.html
 *
 * @package Translation
 *
 * @api This class may be instantiated directly or created using the path factory above.
 */
final class TranslatableMessage implements TranslatableInterface
{
    /**
     * Creates a translatable message.
     *
     * @param string $message
     *   A message containing optional placeholders corresponding to parameters (next). Example:
     *   ```php
     *   $message = 'Email %name at <a href="mailto:%email">%email</a>.';
     *   ```
     * @param \PhpTuf\ComposerStager\Domain\Value\Translation\TranslationParametersInterface|null $parameters
     *   Parameters for the message.
     * @param string|null $domain
     *   An arbitrary domain for grouping translations, e.g., "app", "admin",
     *   "store", or null to use the default.
     */
    public function __construct(
        private readonly string $message,
        private readonly ?TranslationParametersInterface $parameters = null,
        private readonly ?string $domain = null,
    ) {
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->message, $this->parameters, $this->domain, $locale);
    }

    public function __toString(): string
    {
        return $this->message;
    }
}
