<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Core;

use PhpTuf\ComposerStager\Domain\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Domain\ProcessOutputCallback\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;

/**
 * Makes the staged changes live by syncing the active directory with the staging directory.
 *
 * @package Core
 *
 * @api
 */
interface CommitterInterface
{
    /**
     * Commits staged changes to the active directory.
     *
     * @param \PhpTuf\ComposerStager\Domain\Path\Value\PathInterface $stagingDir
     *   The staging directory.
     * @param \PhpTuf\ComposerStager\Domain\Path\Value\PathInterface $activeDir
     *   The active directory.
     * @param \PhpTuf\ComposerStager\Domain\Path\Value\PathListInterface|null $exclusions
     *   Paths to exclude, relative to the staging directory. With rare exception,
     *   you should use the same exclusions when committing as when beginning.
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
     *
     * @see \PhpTuf\ComposerStager\Domain\Core\BeginnerInterface::begin
     */
    public function commit(
        PathInterface $stagingDir,
        PathInterface $activeDir,
        ?PathListInterface $exclusions = null,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT,
    ): void;
}
