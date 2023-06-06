<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Value\Translation;

/**
 * Handles an array of translation parameters.
 *
 * This interface is designed for parity with the Symfony Translation component.
 * It corresponds essentially to Symfony Translation Contracts with slightly
 * stricter placeholder validation rules to conform to Drupal conventions.
 *
 * @see \Symfony\Contracts\Translation\TranslatorInterface
 *
 * @package Translation
 *
 * @api
 */
interface TranslationParametersInterface
{
    /**
     * Returns all translation parameters.
     *
     * @return array<string, string>
     *   An associative array keyed by placeholders with their corresponding substitution
     *   values. Placeholders must be in the form /^%\w+$/, i.e., a leading percent sign (%)
     *   followed by one or more alphanumeric characters and underscores, e.g., "%example".
     *   Values must be strings. Example:
     *   ```php
     *   $parameters = [
     *     '%name' => 'John',
     *     '%email' => 'john@example.com',
     *   ];
     *   ```
     */
    public function getAll(): array;
}
