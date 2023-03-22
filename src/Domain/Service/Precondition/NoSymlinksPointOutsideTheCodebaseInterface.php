<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

/**
 * Asserts that there are no symlinks that point outside the codebase.
 *
 * This includes both the active and staging directories. Hard links are ignored.
 *
 * It doesn't matter whether the given directories actually exist. In order to isolate failures and avoid redundancy,
 * that question is left to its own preconditions. Except in the event of an IO error (which will throw an exception
 * according to the relevant interface), this one cares about literally nothing else if it doesn't actually find a
 * link pointing outside the codebase.
 *
 * This interface exists solely to facilitate autowiring dependencies through type hinting.
 *
 * @see https://symfony.com/doc/current/service_container/autowiring.html
 *
 * @api
 */
interface NoSymlinksPointOutsideTheCodebaseInterface extends PreconditionInterface
{
}
