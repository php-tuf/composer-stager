<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\FileCopierInterface;

final class Beginner implements BeginnerInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\FileCopierInterface
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

    public function begin(
        string $activeDir,
        string $stagingDir,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void {
        if (!$this->filesystem->exists($activeDir)) {
            throw new DirectoryNotFoundException($activeDir, 'The active directory does not exist at "%s"');
        }

        if ($this->filesystem->exists($stagingDir)) {
            throw new DirectoryAlreadyExistsException($stagingDir, 'The staging directory already exists at "%s"');
        }

        // @todo Figure out how to let clients provide their own exclusions.
        $exclusions = [
            $stagingDir,
            '.git',
        ];

        $this->fileCopier->copy(
            $activeDir,
            $stagingDir,
            $exclusions,
            $callback,
            $timeout
        );
    }
}
