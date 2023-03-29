<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\ProcessRunner;

/**
 * Runs shell processes.
 *
 * @package ProcessRunner
 *
 * @api
 */
interface ProcessRunnerInterface
{
    /** The default process timeout. */
    public const DEFAULT_TIMEOUT = 120;
}
