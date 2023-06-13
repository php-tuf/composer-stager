<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Core;

use PhpTuf\ComposerStager\Domain\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Domain\ProcessOutputCallback\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\ProcessRunner\Service\ProcessRunnerInterface;

/**
 * Removes the staging directory.
 *
 * @package Core
 *
 * @api
 */
interface CleanerInterface
{
    /**
     * Removes the staging directory.
     *
     * @param \PhpTuf\ComposerStager\Domain\Path\Value\PathInterface $activeDir
     *   The active directory.
     * @param \PhpTuf\ComposerStager\Domain\Path\Value\PathInterface $stagingDir
     *   The staging directory.
     * @param \PhpTuf\ComposerStager\Domain\ProcessOutputCallback\Service\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
     *   If the preconditions are unfulfilled.
     * @throws \PhpTuf\ComposerStager\Domain\Exception\RuntimeException
     *   If the operation fails.
     */
    public function clean(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT,
    ): void;
}
