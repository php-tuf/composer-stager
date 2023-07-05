<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Translation\Service;

use Symfony\Contracts\Translation\TranslatorInterface as SymfonyTranslatorInterface;

/**
 * Holds and provides an anti-corruption layer around the Symfony translator.
 *
 * @package Translation
 *
 * @internal Don't depend directly on this interface. It may be changed or removed at any time without notice.
 */
interface SymfonyTranslatorProxyInterface extends SymfonyTranslatorInterface
{
}
