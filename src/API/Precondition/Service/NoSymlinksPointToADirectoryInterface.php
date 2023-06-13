<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Precondition\Service;

/**
 * Asserts that there are no symlinks that point to directories.
 *
 * Symlinks targeting directories are supported by RsyncFileSyncer but not yet by
 * PhpFileSyncer. Therefore, they are forbidden when the latter is in use. Once they
 * are supported by both file syncers, this precondition will be removed completely.
 *
 * This includes both the active and staging directories. Hard links are ignored.
 *
 * It doesn't matter whether the given directories actually exist. In order to
 * isolate failures and avoid redundancy, that question is left to its own
 * preconditions. Except in the event of an IO error (which will throw an
 * exception according to the relevant interface), this one cares about literally
 * nothing else if it doesn't actually find a link pointing outside the codebase.
 *
 * This interface exists solely to facilitate autowiring dependencies through type hinting.
 *
 * @see https://github.com/php-tuf/composer-stager/issues/99
 * @see https://symfony.com/doc/current/service_container/autowiring.html
 *
 * @package Precondition
 *
 * @api
 */
interface NoSymlinksPointToADirectoryInterface extends PreconditionInterface
{
}
