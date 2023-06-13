<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\FileSyncer\Service;

use PhpTuf\ComposerStager\Domain\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Domain\ProcessOutputCallback\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;

/**
 * Recursively syncs files from one directory to another.
 *
 * @package FileSyncer
 *
 * @api
 */
interface FileSyncerInterface
{
    /**
     * Recursively syncs files from one directory to another.
     *
     * Files in the destination will be overwritten by those in the source, even if
     * newer. Files in the destination that do not exist in the source will be deleted.
     * Excluded paths will be completely ignored and neither copied to nor deleted
     * from the destination. If the destination does not exist, it will be created.
     *
     * @param \PhpTuf\ComposerStager\Domain\Path\Value\PathInterface $source
     *   The directory to sync files from.
     * @param \PhpTuf\ComposerStager\Domain\Path\Value\PathInterface $destination
     *   The directory to sync files to.
     * @param \PhpTuf\ComposerStager\Domain\Path\Value\PathListInterface|null $exclusions
     *   Paths to exclude, relative to the source directory. The destination
     *   directory is automatically excluded in order to prevent infinite
     *   recursion if it is a descendant of the source directory, i.e., if it is
     *   "underneath" or "inside" it.
     * @param \PhpTuf\ComposerStager\Domain\ProcessOutputCallback\Service\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\IOException
     *   If the destination directory cannot be created.
     * @throws \PhpTuf\ComposerStager\Domain\Exception\LogicException
     *   If the source directory does not exist or is the same as the destination.
     */
    public function sync(
        PathInterface $source,
        PathInterface $destination,
        ?PathListInterface $exclusions = null,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = ProcessRunnerInterface::DEFAULT_TIMEOUT,
    ): void;
}
