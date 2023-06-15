<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Translation\Factory;

use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;

/**
 * Provides a convenience method for creating translatable objects.
 *
 * @package Translation
 *
 * @api This trait is subject to our backward compatibility promise and may be safely depended upon.
 */
trait TranslatableAwareTrait
{
    private ?TranslatableFactoryInterface $translatableFactory = null;

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
    protected function t(
        string $message,
        ?TranslationParametersInterface $parameters = null,
        ?string $domain = null,
    ): TranslatableInterface {
        assert(
            $this->translatableFactory instanceof TranslatableFactoryInterface,
            'The "t()" method requires a translatable factory. '
            . 'Provide one by calling "setTranslatableFactory()" in the constructor.',
        );

        return $this->translatableFactory->createTranslatableMessage($message, $parameters, $domain);
    }

    /**
     * Creates a translation parameters object.
     *
     * @param array<string, string> $parameters
     *   An associative array keyed by placeholders with their corresponding substitution
     *   values. Placeholders must be in the form /^%\w+$/, i.e., a leading percent sign (%)
     *   followed by one or more alphanumeric characters and underscores, e.g., "%example".
     *   Values must be strings. Example:
     *   ```php
     *   $parameters = [
     *     '%first_name' => 'John',
     *     '%last_name' => 'Doe',
     *   ];
     *   ```
     */
    protected function p(array $parameters = []): TranslationParametersInterface
    {
        assert(
            $this->translatableFactory instanceof TranslatableFactoryInterface,
            'The "p()" method requires a translatable factory. '
            . 'Provide one by calling "setTranslatableFactory()" in the constructor.',
        );

        return $this->translatableFactory->createTranslationParameters($parameters);
    }

    private function setTranslatableFactory(TranslatableFactoryInterface $translatableFactory): void
    {
        $this->translatableFactory = $translatableFactory;
    }
}
