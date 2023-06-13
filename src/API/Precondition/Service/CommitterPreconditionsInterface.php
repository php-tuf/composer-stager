<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Precondition\Service;

/**
 * Asserts the preconditions for the committer.
 *
 * This interface exists solely to facilitate autowiring dependencies through type hinting.
 *
 * @see https://symfony.com/doc/current/service_container/autowiring.html
 *
 * @package Precondition
 *
 * @api
 */
interface CommitterPreconditionsInterface extends PreconditionInterface
{
}
