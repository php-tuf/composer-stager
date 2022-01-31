<?php

namespace PhpTuf\ComposerStager\Infrastructure\FileSyncer;

use PhpTuf\ComposerStager\Domain\Aggregate\PathAggregate\PathAggregateInterface;
use PhpTuf\ComposerStager\Domain\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Process\Runner\RsyncRunnerInterface;
use PhpTuf\ComposerStager\Infrastructure\Aggregate\PathAggregate\PathAggregate;

final class RsyncFileSyncer implements FileSyncerInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface
     */
    private $filesystem;

    /**
     * @var \PhpTuf\ComposerStager\Domain\Process\Runner\RsyncRunnerInterface
     */
    private $rsync;

    public function __construct(FilesystemInterface $filesystem, RsyncRunnerInterface $rsync)
    {
        $this->filesystem = $filesystem;
        $this->rsync = $rsync;
    }

    /** @noinspection CallableParameterUseCaseInTypeContextInspection */
    public function sync(
        PathInterface $source,
        PathInterface $destination,
        PathAggregateInterface $exclusions = null,
        OutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void {
        $exclusions = $exclusions ?? new PathAggregate([]);

        if (!$this->filesystem->exists($source->resolve())) {
            throw new DirectoryNotFoundException($source->resolve(), 'The source directory does not exist at "%s"');
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

        $exclusions = array_map(function ($path) use ($destination): string {
            return $this::getRelativePath(
                $destination->resolve(),
                $path->resolveRelativeTo($destination)
            );
        }, $exclusions->getAll());

        // Prevent infinite recursion if the source is inside the destination.
        $exclusions[] = $source->resolve();

        // There's no reason to process duplicates.
        $exclusions = array_unique($exclusions);

        foreach ($exclusions as $exclusion) {
            $command[] = '--exclude=' . $exclusion;
        }

        // A trailing slash is added to the source directory so the CONTENTS
        // of the directory are synced, not the directory itself.
        $command[] = $source->resolve() . DIRECTORY_SEPARATOR;

        $command[] = $destination->resolve();

        // Ensure the destination directory's existence. (This has no effect
        // if it already exists.)
        $this->filesystem->mkdir($destination->resolve());
        try {
            $this->rsync->run($command, $callback);
        } catch (ExceptionInterface $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    private static function getRelativePath(string $ancestor, string $path): string
    {
        $ancestor .= DIRECTORY_SEPARATOR;
        if (strpos($path, $ancestor) === 0) {
            $path = substr($path, strlen($ancestor));
        }
        return $path;
    }
}
