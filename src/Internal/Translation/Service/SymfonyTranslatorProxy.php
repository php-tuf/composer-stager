<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Translation\Service;

use PhpTuf\ComposerStager\API\Translation\Value\LocaleInterface;
use Symfony\Contracts\Translation\TranslatorInterface as SymfonyTranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

/**
 * Provides a thin wrapper around Symfony's default translator implementation.
 *
 * This is necessary because Symfony Translation Contracts doesn't provide an
 * injectable class--only a trait--and we don't want to depend on the full
 * Translation component to get one. Neither do we want to fork any part of it.
 *
 * @package Translation
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class SymfonyTranslatorProxy implements SymfonyTranslatorProxyInterface
{
    private readonly SymfonyTranslatorInterface $symfonyTranslator;

    public function __construct()
    {
        // Wrap the translator trait rather than using it directly
        // so as not to expose methods that aren't on the interface.
        $this->symfonyTranslator = new class() implements SymfonyTranslatorInterface {
            use TranslatorTrait;
        };
    }

    public function trans(
        string $id,
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = LocaleInterface::DEFAULT,
    ): string {
        return $this->symfonyTranslator->trans($id, $parameters, $domain, $locale);
    }

    public function getLocale(): string
    {
        // The Symfony translator trait returns different values based on
        // host details. Eliminate the variability by hard coding a value.
        return LocaleInterface::DEFAULT;
    }
}
