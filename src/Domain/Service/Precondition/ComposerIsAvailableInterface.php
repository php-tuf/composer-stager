<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

/**
 * Determines whether the Composer executable is available.
 *
 * This interface exists solely to facilitate autowiring dependencies through type hinting.
 *
 * @see https://symfony.com/doc/current/service_container/autowiring.html
 */
interface ComposerIsAvailableInterface extends PreconditionInterface
{
}
