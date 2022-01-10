<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\FileSyncer\FileSyncerInterface;

final class Beginner implements BeginnerInterface
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

    public function begin(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        array $exclusions = [],
        ?OutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void {
        $activeDirResolved = $activeDir->getResolved();
        if (!$this->filesystem->exists($activeDirResolved)) {
            throw new DirectoryNotFoundException($activeDirResolved, 'The active directory does not exist at "%s"');
        }

        $stagingDirResolved = $stagingDir->getResolved();
        if ($this->filesystem->exists($stagingDirResolved)) {
            throw new DirectoryAlreadyExistsException($stagingDirResolved, 'The staging directory already exists at "%s"');
        }

        try {
            $this->fileSyncer->sync(
                $activeDir,
                $stagingDir,
                $exclusions,
                $callback,
                $timeout
            );
        } catch (IOException $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
