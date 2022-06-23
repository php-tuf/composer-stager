<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

/**
 * Asserts that the codebase contains no symlinks.
 *
 * This includes the active directory and, if it exists, the staging directory.
 *
 * This interface exists solely to facilitate autowiring dependencies through type hinting.
 *
 * @see https://symfony.com/doc/current/service_container/autowiring.html
 */
interface CodebaseContainsNoSymlinksInterface extends PreconditionInterface
{
}
