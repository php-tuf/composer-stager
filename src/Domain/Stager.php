<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Exception\LogicException;
use PhpTuf\ComposerStager\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Process\ProcessFactory;

class Stager
{
    /**
     * @var array
     */
    private $command;

    /**
     * @var \PhpTuf\ComposerStager\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * @var \PhpTuf\ComposerStager\Process\ProcessFactory
     */
    private $processFactory;

    /**
     * @var string
     */
    private $stagingDir;

    public function __construct(Filesystem $filesystem, ProcessFactory $processFactory)
    {
        $this->filesystem = $filesystem;
        $this->processFactory = $processFactory;
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     * @throws \PhpTuf\ComposerStager\Exception\LogicException
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException
     */
    public function stage(array $command, string $stagingDir): void
    {
        $this->command = $command;
        $this->stagingDir = $stagingDir;
        $this->validateCommand();
        $this->validatePreconditions();
        $this->runCommand();
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\LogicException
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException
     */
    private function validateCommand(): void
    {
        if (!$this->command) {
            throw new LogicException('The command cannot be empty.');
        }
        if (array_key_exists('--working-dir', $this->command)
            || array_key_exists('-d', $this->command)) {
            throw new InvalidArgumentException('Cannot use the "--working-dir" (or "-d") options');
        }
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

    private function runCommand(): void
    {
        $this->processFactory
            ->create(array_merge([
                'composer',
                sprintf('--working-dir=%s', $this->stagingDir),
            ], $this->command));
    }
}
