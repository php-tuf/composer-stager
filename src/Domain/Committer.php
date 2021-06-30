<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopierInterface;

final class Committer implements CommitterInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Process\FileCopierInterface
     */
    private $fileCopier;

    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface
     */
    private $filesystem;

    public function __construct(FileCopierInterface $fileCopier, FilesystemInterface $filesystem)
    {
        $this->fileCopier = $fileCopier;
        $this->filesystem = $filesystem;
    }

    public function commit(string $stagingDir, string $activeDir, ?ProcessOutputCallbackInterface $callback = null): void
    {
        if (!$this->filesystem->exists($stagingDir)) {
            throw new DirectoryNotFoundException($stagingDir, 'The staging directory does not exist at "%s"');
        }

        if (!$this->filesystem->exists($activeDir)) {
            throw new DirectoryNotFoundException($activeDir, 'The active directory does not exist at "%s"');
        }

        if (!$this->filesystem->isWritable($activeDir)) {
            throw new DirectoryNotWritableException($activeDir, 'The active directory is not writable at "%s"');
        }

        $this->fileCopier->copy($stagingDir, $activeDir, [], $callback);
    }

    public function directoryExists(string $stagingDir): bool
    {
        return $this->filesystem->exists($stagingDir);
    }
}
