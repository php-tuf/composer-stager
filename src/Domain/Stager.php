<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Filesystem\Filesystem;

class Stager
{
    /**
     * @var \PhpTuf\ComposerStager\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $stagingDir;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     */
    public function stage(string $stagingDir): void
    {
        $this->stagingDir = $stagingDir;
        $this->validatePreconditions();
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     */
    private function validatePreconditions(): void
    {
        if (!$this->filesystem->exists($this->stagingDir)) {
            throw new DirectoryNotFoundException($this->stagingDir, 'The staging directory does not exist at "%s"');
        }
        if (!$this->filesystem->isWritable($this->stagingDir)) {
            throw new DirectoryNotWritableException($this->stagingDir, 'The staging directory is not writable at "%s"');
        }
    }
}
