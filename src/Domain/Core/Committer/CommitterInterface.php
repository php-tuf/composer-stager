<?php

namespace PhpTuf\ComposerStager\Domain\Core\Committer;

use PhpTuf\ComposerStager\Domain\Aggregate\PathAggregate\PathAggregateInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

/**
 * Makes the staged changes live by syncing the active directory with the staging directory.
 */
interface CommitterInterface
{
    /**
     * Commits staged changes to the active directory.
     *
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $stagingDir
     *   The staging directory.
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $activeDir
     *   The active directory.
     * @param \PhpTuf\ComposerStager\Domain\Aggregate\PathAggregate\PathAggregateInterface|null $exclusions
     *   Paths to exclude, relative to the staging directory. With rare exception,
     *   you should use the same exclusions when committing as when beginning.
     * @param \PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @see \PhpTuf\ComposerStager\Domain\Core\Beginner\BeginnerInterface::begin
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     *   If the active directory or the staging directory is not found.
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     *   If the active directory is not writable.
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException
     *   If $exclusions includes invalid paths.
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     *   If the command process doesn't terminate successfully.
     */
    public function commit(
        PathInterface $stagingDir,
        PathInterface $activeDir,
        PathAggregateInterface $exclusions = null,
        ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void;

    /**
     * Determines whether the staging directory exists.
     *
     * @param string $stagingDir
     *   The staging directory.
     */
    public function directoryExists(string $stagingDir): bool;
}
