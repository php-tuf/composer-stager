<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;
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
        string $activeDir,
        string $stagingDir,
        array $exclusions = [],
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void {
        if (!$this->filesystem->exists($activeDir)) {
            throw new DirectoryNotFoundException($activeDir, 'The active directory does not exist at "%s"');
        }

        if ($this->filesystem->exists($stagingDir)) {
            throw new DirectoryAlreadyExistsException($stagingDir, 'The staging directory already exists at "%s"');
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
