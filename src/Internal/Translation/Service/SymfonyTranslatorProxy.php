<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Translation\Service;

use Symfony\Contracts\Translation\TranslatorInterface as SymfonyTranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait as SymfonyTranslatorTrait;

/**
 * @package Translation
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class SymfonyTranslatorProxy implements SymfonyTranslatorProxyInterface
{
    // The Symfony translator trait returns different values based on
    // host details. Eliminate the variability by hard coding a value.
    private const LOCALE = 'en_US';

    private readonly SymfonyTranslatorInterface $symfonyTranslator;

    public function __construct()
    {
        // Wrap the translator trait rather than using it directly
        // so as not to expose methods that aren't on the interface.
        $this->symfonyTranslator = new class() implements SymfonyTranslatorInterface {
            use SymfonyTranslatorTrait;
        };
    }

    /** @noinspection PhpParameterNameChangedDuringInheritanceInspection */
    public function trans(
        string $message,
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = self::LOCALE,
    ): string {
        return $this->symfonyTranslator->trans($message, $parameters, $domain, $locale);
    }

    public function getLocale(): string
    {
        return self::LOCALE;
    }
}
