<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Core;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface;

/**
 * Makes the staged changes live by syncing the active directory with the staging directory.
 *
 * @package Core
 *
 * @api This interface is subject to our backward compatibility promise and may be safely depended upon.
 */
interface CommitterInterface
{
    /**
     * Commits staged changes to the active directory.
     *
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $stagingDir
     *   The staging directory.
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface $activeDir
     *   The active directory.
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathListInterface|null $exclusions
     *   Paths to exclude, relative to the staging directory. With rare exception,
     *   you should use the same exclusions when committing as when beginning.
     * @param \PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\PreconditionException
     *   If the preconditions are unfulfilled.
     * @throws \PhpTuf\ComposerStager\API\Exception\RuntimeException
     *   If the operation fails.
     *
     * @see \PhpTuf\ComposerStager\API\Core\BeginnerInterface::begin
     */
    public function commit(
        PathInterface $stagingDir,
        PathInterface $activeDir,
        ?PathListInterface $exclusions = null,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessInterface::DEFAULT_TIMEOUT,
    ): void;
}
