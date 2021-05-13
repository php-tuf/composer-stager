<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Infrastructure\Process\ComposerFinder;
use PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactory;

class Stager
{
    /**
     * @var string[]
     */
    private $composerCommand;

    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Process\ComposerFinder
     */
    private $composerFinder;

    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem
     */
    private $filesystem;

    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactory
     */
    private $processFactory;

    /**
     * @var string
     */
    private $stagingDir;

    public function __construct(
        ComposerFinder $composerFinder,
        Filesystem $filesystem,
        ProcessFactory $processFactory
    ) {
        $this->composerFinder = $composerFinder;
        $this->filesystem = $filesystem;
        $this->processFactory = $processFactory;
    }

    /**
     * @param string[] $composerCommand
     *   The Composer command parts exactly as they would be typed in the
     *   terminal. There's no need to escape them in any way, only to separate
     *   them. Example:
     *
     *   @code{.php}
     *   $command = [
     *     // "composer" is implied.
     *     'require',
     *     'lorem/ipsum:"^1 || ^2"',
     *     '--with-all-dependencies',
     *   ];
     *   @endcode
     *
     *   @see https://symfony.com/doc/current/components/process.html#running-processes-asynchronously
     *
     * @param string $stagingDir
     * @param callable|null $callback A PHP callback to run whenever there is
     *   some output available on STDOUT or STDERR.
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     * @throws \PhpTuf\ComposerStager\Exception\FileNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException
     */
    public function stage(array $composerCommand, string $stagingDir, callable $callback = null): void
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
            throw new InvalidArgumentException('The composer-command argument cannot be empty');
        }
        if (reset($this->composerCommand) === 'composer') {
            throw new InvalidArgumentException('The composer-command argument cannot begin with "composer"');
        }
        if (array_key_exists('--working-dir', $this->composerCommand)
            || array_key_exists('-d', $this->composerCommand)) {
            throw new InvalidArgumentException('Cannot stage a command containing the "--working-dir" (or "-d") option');
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
     * @throws \PhpTuf\ComposerStager\Exception\FileNotFoundException
     */
    private function runCommand(?callable $callback): void
    {
        $process = $this->processFactory
            ->create(array_merge([
                $this->composerFinder->find(),
                "--working-dir={$this->stagingDir}",
            ], $this->composerCommand));
        try {
            $process->mustRun($callback);
        } catch (\Symfony\Component\Process\Exception\ProcessFailedException $e) {
            throw new ProcessFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
