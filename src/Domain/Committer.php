<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Domain\Aggregate\PathAggregate\PathAggregateInterface;
use PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\FileSyncer\FileSyncerInterface;

final class Committer implements CommitterInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Domain\FileSyncer\FileSyncerInterface
     */
    private $fileSyncer;

    /**
     * @var \PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface
     */
    private $filesystem;

    public function __construct(FileSyncerInterface $fileSyncer, FilesystemInterface $filesystem)
    {
        $this->fileSyncer = $fileSyncer;
        $this->filesystem = $filesystem;
    }

    public function commit(
        PathInterface $stagingDir,
        PathInterface $activeDir,
        PathAggregateInterface $exclusions = null,
        OutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void {
        $stagingDirResolved = $stagingDir->getResolved();
        if (!$this->filesystem->exists($stagingDirResolved)) {
            throw new DirectoryNotFoundException($stagingDirResolved, 'The staging directory does not exist at "%s"');
        }

        $activeDirResolved = $activeDir->getResolved();
        if (!$this->filesystem->exists($activeDirResolved)) {
            throw new DirectoryNotFoundException($activeDirResolved, 'The active directory does not exist at "%s"');
        }

        if (!$this->filesystem->isWritable($activeDirResolved)) {
            throw new DirectoryNotWritableException($activeDirResolved, 'The active directory is not writable at "%s"');
        }

        $exclusionList = $exclusions === null ? [] : $exclusions->getAll();
        $exclusionList = array_map(static function ($path): string {
            return $path->getResolved();
        }, $exclusionList);
        try {
            $this->fileSyncer->sync($stagingDir, $activeDir, $exclusionList, $callback, $timeout);
        } catch (IOException $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function directoryExists(string $stagingDir): bool
    {
        return $this->filesystem->exists($stagingDir);
    }
}
