<?php

namespace PhpTuf\ComposerStager\Infrastructure\FileSyncer;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunnerInterface;
use PhpTuf\ComposerStager\Util\PathUtil;

/**
 * @internal
 */
final class RsyncFileSyncer implements FileSyncerInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface
     */
    private $filesystem;

    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunnerInterface
     */
    private $rsync;

    public function __construct(FilesystemInterface $filesystem, RsyncRunnerInterface $rsync)
    {
        $this->filesystem = $filesystem;
        $this->rsync = $rsync;
    }

    public function sync(
        string $source,
        string $destination,
        array $exclusions = [],
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void {
        if (!$this->filesystem->exists($source)) {
            throw new DirectoryNotFoundException($source, 'The source directory does not exist at "%s"');
        }

        $command = [
            // Archive mode--the same as -rlptgoD (no -H), or --recursive,
            // --links, --perms, --times, --group, --owner, --devices, --specials.
            '--archive',
            // Delete extraneous files from destination directories. Note: Using
            // --delete-after rather than alternatives prevents "file has
            // vanished" errors when syncing a directory with its own ancestor.
            '--delete-after',
            // Increase verbosity.
            '--verbose',
        ];

        // Prevent infinite recursion if the source is inside the destination.
        $exclusions[] = $source;

        $exclusions = array_unique($exclusions);

        foreach ($exclusions as $exclusion) {
            $command[] = '--exclude=' . $exclusion;
        }

        // A trailing slash is added to the source directory so the CONTENTS
        // of the directory are synced, not the directory itself.
        $command[] = PathUtil::ensureTrailingSlash($source);

        $command[] = PathUtil::ensureTrailingSlash($destination);

        // Ensure the destination directory's existence. (This has no effect
        // if it already exists.)
        $this->filesystem->mkdir($destination);
        try {
            $this->rsync->run($command, $callback);
        } catch (ExceptionInterface $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
