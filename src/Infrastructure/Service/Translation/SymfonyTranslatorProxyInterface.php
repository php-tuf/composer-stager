<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Translation;

use Symfony\Contracts\Translation\TranslatorInterface as SymfonyTranslatorInterface;

/**
 * Holds and provides an anti-corruption layer around the Symfony translator.
 *
 * @package Translation
 *
 * @internal Don't depend on this interface. It may be changed or removed without notice.
 */
interface SymfonyTranslatorProxyInterface extends SymfonyTranslatorInterface
{
}
