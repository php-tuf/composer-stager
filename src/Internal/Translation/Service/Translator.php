<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Translation\Service;

use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\API\Translation\Value\LocaleInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslationParametersInterface;
use PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters;
use Throwable;

/**
 * @package Translation
 *
 * This class HAPPENS to use a Symfony translator. However, there is no guarantee of
 * functional equivalence. Refer to the interface and do not depend on undocumented behavior.
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class Translator implements TranslatorInterface
{
    public function __construct(private readonly SymfonyTranslatorProxyInterface $symfonyTranslatorProxy)
    {
    }

    public function trans(
        string $id,
        ?TranslationParametersInterface $parameters = null,
        ?string $domain = null,
        ?string $locale = null,
    ): string {
        try {
            $parameters ??= new TranslationParameters();

            return $this->symfonyTranslatorProxy->trans($id, $parameters->getAll(), $domain, $locale);
        } catch (Throwable $e) {
            $message = sprintf('Translation error: %s', $e->getMessage());

            // Re/throwing an exception here would create a nasty infinite recursion
            // problem that would end up exposing client code to low-level errors
            // with virtually every service call. Instead, use an assertion to surface
            // errors during development and return the untranslated error message
            // in case any escape to production.
            assert(false, $message);

            /** @noinspection PhpUnreachableStatementInspection */
            return $message;
        }
    }

    public function getLocale(): string
    {
        return LocaleInterface::DEFAULT;
    }
}
