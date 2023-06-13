<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Translation\Value;

use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;

/**
 * Handles a translatable message.
 *
 * @see \PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface
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
     *   $message = 'Hello, %first_name %last_name.';
     *   ```
     * @param \PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface|null $parameters
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
