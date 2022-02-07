<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Core\Cleaner;

use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

/**
 * Removes the staging directory.
 */
interface CleanerInterface
{
    /**
     * Removes the staging directory.
     *
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $stagingDir
     *   The staging directory.
     * @param \PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\DirectoryNotFoundException
     *   If the staging directory is not found.
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     *   If removal fails.
     */
    public function clean(
        PathInterface $stagingDir,
        ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT
    ): void;

    /**
     * Determines whether the staging directory exists.
     *
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $stagingDir
     *   The staging directory.
     */
    public function directoryExists(PathInterface $stagingDir): bool;
}
