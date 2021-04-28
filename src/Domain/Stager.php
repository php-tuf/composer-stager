<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Exception\LogicException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Process\ProcessFactory;
use Symfony\Component\Process\Process;

class Stager
{
    /**
     * @var string[]
     */
    private $composerCommand;

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
     * @param string[] $composerCommand
     *   The Composer command parts exactly as they would be typed in the
     *   terminal. Example:
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
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException
     * @throws \PhpTuf\ComposerStager\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    public function stage(array $composerCommand, string $stagingDir): string
    {
        $this->composerCommand = $composerCommand;
        $this->stagingDir = $stagingDir;
        $this->validate();
        return $this->runCommand();
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\LogicException
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
     * @throws \PhpTuf\ComposerStager\Exception\LogicException
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException
     */
    private function validateCommand(): void
    {
        if ($this->composerCommand === []) {
            throw new LogicException('The command cannot be empty.');
        }
        if (array_key_exists('--working-dir', $this->composerCommand)
            || array_key_exists('-d', $this->composerCommand)) {
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

    /**
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    private function runCommand(): string
    {
        $process = $this->processFactory
            ->create(array_merge([
                'composer',
                "--working-dir={$this->stagingDir}",
            ], $this->composerCommand));
        try {
            $process->mustRun();
            return $this->parseOutput($process);
        } catch (\Symfony\Component\Process\Exception\ProcessFailedException $e) {
            throw new ProcessFailedException($e->getMessage());
        }
    }

    private function parseOutput(Process $process): string
    {
        $output = [$process->getOutput(), $process->getErrorOutput()];
        $output = array_filter($output);
        return implode(PHP_EOL, $output);
    }
}
