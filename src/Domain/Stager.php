<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Process\Runner\ComposerRunnerInterface;

final class Stager implements StagerInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Domain\Process\Runner\ComposerRunnerInterface
     */
    private $composerRunner;

    /**
     * @var \PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface
     */
    private $filesystem;

    public function __construct(
        ComposerRunnerInterface $composerRunner,
        FilesystemInterface $filesystem
    ) {
        $this->composerRunner = $composerRunner;
        $this->filesystem = $filesystem;
    }

    public function stage(
        array $composerCommand,
        PathInterface $stagingDir,
        OutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void {
        $this->validate($stagingDir, $composerCommand);
        $this->runCommand($stagingDir, $composerCommand, $callback, $timeout);
    }

    /**
     * @param string[] $composerCommand
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException
     */
    private function validate(PathInterface $stagingDir, array $composerCommand): void
    {
        $this->validateCommand($composerCommand);
        $this->validatePreconditions($stagingDir);
    }

    /**
     * @param string[] $composerCommand
     *
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException
     */
    private function validateCommand(array $composerCommand): void
    {
        if ($composerCommand === []) {
            throw new InvalidArgumentException('The Composer command cannot be empty');
        }
        if (reset($composerCommand) === 'composer') {
            throw new InvalidArgumentException('The Composer command cannot begin with "composer"--it is implied');
        }
        if (array_key_exists('--working-dir', $composerCommand)
            || array_key_exists('-d', $composerCommand)) {
            throw new InvalidArgumentException('Cannot stage a Composer command containing the "--working-dir" (or "-d") option');
        }
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     */
    private function validatePreconditions(PathInterface $stagingDir): void
    {
        $stagingDirResolved = $stagingDir->resolve();
        if (!$this->filesystem->exists($stagingDirResolved)) {
            throw new DirectoryNotFoundException($stagingDirResolved, 'The staging directory does not exist at "%s"');
        }
        if (!$this->filesystem->isWritable($stagingDirResolved)) {
            throw new DirectoryNotWritableException($stagingDirResolved, 'The staging directory is not writable at "%s"');
        }
    }

    /**
     * @param string[] $composerCommand
     *
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     */
    private function runCommand(PathInterface $stagingDir, array $composerCommand, ?OutputCallbackInterface $callback, ?int $timeout): void
    {
        $command = array_merge(
            ['--working-dir=' . $stagingDir->resolve()],
            $composerCommand
        );
        try {
            $this->composerRunner->run($command, $callback, $timeout);
        } catch (ExceptionInterface $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
