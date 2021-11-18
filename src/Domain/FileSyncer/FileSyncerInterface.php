<?php

namespace PhpTuf\ComposerStager\Domain\FileSyncer;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;

/**
 * Recursively syncs files from one directory to another.
 */
interface FileSyncerInterface
{
    /**
     * Recursively syncs files, including symlinks, from one directory to another.
     *
     * Files in the destination will be overwritten by those in the source, even if
     * newer. Files in the destination that do not exist in the source will be deleted.
     * Excluded paths will be completely ignored and neither copied to nor deleted
     * from the destination. If the destination does not exist, it will be created.
     * Remote filesystems are not supported.
     *
     * @param string $source
     *   The directory to sync files from, as an absolute path or relative to the
     *   current working directory (CWD), e.g., "/var/www/source" or "source".
     * @param string $destination
     *   The directory to sync files to, as an absolute path or relative to the
     *   current working directory (CWD), e.g., "/var/www/destination" or
     *   "destination". If it does not exist it will be created.
     * @param string[] $exclusions
     *   An array of paths to exclude, relative to the source directory. Absolute
     *   paths are silently ignored. The destination directory is automatically
     *   excluded in order to prevent infinite recursion if it is a descendant of
     *   the source directory (i.e., if it is "underneath" or "inside" it).
     * @param \PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     *   If the source directory is not found.
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     *   If the destination directory cannot be created.
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     *   If the command process doesn't terminate successfully.
     */
    public function sync(
        string $source,
        string $destination,
        array $exclusions = [],
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void;
}
