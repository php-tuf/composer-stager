<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Core\Committer;

use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;

/**
 * Makes the staged changes live by syncing the active directory with the staging directory.
 *
 * @api
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
     * @param \PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface|null $exclusions
     *   Paths to exclude, relative to the staging directory. With rare exception,
     *   you should use the same exclusions when committing as when beginning.
     * @param \PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException
     *   If $exclusions includes invalid paths.
     * @throws \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
     *   If the preconditions are unfulfilled.
     * @throws \PhpTuf\ComposerStager\Domain\Exception\RuntimeException
     *   If the operation fails.
     *
     * @see \PhpTuf\ComposerStager\Domain\Core\Beginner\BeginnerInterface::begin
     */
    public function commit(
        PathInterface $stagingDir,
        PathInterface $activeDir,
        ?PathListInterface $exclusions = null,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT,
    ): void;
}
