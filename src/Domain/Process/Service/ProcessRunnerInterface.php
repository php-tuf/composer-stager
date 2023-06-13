<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Process\Service;

/**
 * Runs shell processes.
 *
 * @package Process
 *
 * @api
 */
interface ProcessRunnerInterface
{
    /** The default process timeout. */
    public const DEFAULT_TIMEOUT = 120;
}
