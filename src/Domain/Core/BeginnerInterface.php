<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Core;

use PhpTuf\ComposerStager\Domain\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;

/**
 * Begins the staging process by copying the active directory to the staging directory.
 *
 * @package Core
 *
 * @api
 */
interface BeginnerInterface
{
    /**
     * Begins the staging process.
     *
     * @param \PhpTuf\ComposerStager\Domain\Path\Value\PathInterface $activeDir
     *   The active directory.
     * @param \PhpTuf\ComposerStager\Domain\Path\Value\PathInterface $stagingDir
     *   The staging directory.
     * @param \PhpTuf\ComposerStager\Domain\Path\Value\PathListInterface|null $exclusions
     *   Paths to exclude, relative to the active directory. Careful use of
     *   exclusions can reduce execution time and disk usage. Two kinds of files
     *   and directories are good candidates for exclusion:
     *   - Those that will (or might) be changed in the active directory between
     *     beginning and committing and should not be overwritten in the active
     *     directory. This might include user upload directories, for example.
     *   - Those that definitely will NOT be changed or needed between beginning
     *     and committing and will therefore have no effect on the final outcome.
     *     This might include your version control directory, e.g., ".git", or
     *     certain kinds of caches, e.g., of HTTP responses.
     *
     *   With rare exception, you should use the same exclusions when beginning
     *   as when committing.
     * @param \PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface|null $callback
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
     * @see \PhpTuf\ComposerStager\Domain\Core\CommitterInterface::commit
     */
    public function begin(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT,
    ): void;
}
