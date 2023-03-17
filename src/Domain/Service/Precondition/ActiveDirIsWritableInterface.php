<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

/**
 * Asserts that the active directory exists.
 *
 * This is not recursive--only the directory itself is tested, not its descendants.
 *
 * This interface exists solely to facilitate autowiring dependencies through type hinting.
 *
 * @see https://symfony.com/doc/current/service_container/autowiring.html
 *
 * @api
 */
interface ActiveDirIsWritableInterface extends PreconditionInterface
{
}
