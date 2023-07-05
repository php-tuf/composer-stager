<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Process\Service;

/**
 * Runs shell processes.
 *
 * @package Process
 *
 * @api This interface is subject to our backward compatibility promise and may be safely depended upon.
 */
interface ProcessRunnerInterface
{
    /** The default process timeout. */
    public const DEFAULT_TIMEOUT = 120;
}
