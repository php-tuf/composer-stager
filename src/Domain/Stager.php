<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Domain\Output\CallbackInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Exception\LogicException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Infrastructure\Process\Runner\ComposerRunner;

final class Stager implements StagerInterface
{
    /**
     * @var string[]
     */
    private $composerCommand;

    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Process\Runner\ComposerRunner
     */
    private $composerRunner;

    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $stagingDir;

    public function __construct(
        ComposerRunner $composerRunner,
        Filesystem $filesystem
    ) {
        $this->composerRunner = $composerRunner;
        $this->filesystem = $filesystem;
    }

    public function stage(array $composerCommand, string $stagingDir, ?CallbackInterface $callback = null): void
    {
        $this->composerCommand = $composerCommand;
        $this->stagingDir = $stagingDir;
        $this->validate();
        $this->runCommand($callback);
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException
     */
    private function validate(): void
    {
        $this->validateCommand();
        $this->validatePreconditions();
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException
     */
    private function validateCommand(): void
    {
        if ($this->composerCommand === []) {
            throw new InvalidArgumentException('The Composer command cannot be empty');
        }
        if (reset($this->composerCommand) === 'composer') {
            throw new InvalidArgumentException('The Composer command cannot begin with "composer"');
        }
        if (array_key_exists('--working-dir', $this->composerCommand)
            || array_key_exists('-d', $this->composerCommand)) {
            throw new InvalidArgumentException('Cannot stage a Composer command containing the "--working-dir" (or "-d") option');
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

    /**
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     */
    private function runCommand(?CallbackInterface $callback): void
    {
        $command = array_merge(
            ['--working-dir=' . $this->stagingDir],
            $this->composerCommand
        );
        try {
            $this->composerRunner->run($command, $callback);
        } catch (LogicException|ProcessFailedException $e) {
            throw new ProcessFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
