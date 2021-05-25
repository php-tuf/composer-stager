<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\Runner;

use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactory;

/**
 * Provides a base for process runners for consistent process creation and
 * exception-handling.
 *
 * @internal
 */
abstract class AbstractRunner
{
    /**
     * Returns the executable name, e.g., "composer" or "rsync".
     */
    abstract protected function executableName(): string;

    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactory
     */
    private $processFactory;

    public function __construct(ProcessFactory $processFactory)
    {
        $this->processFactory = $processFactory;
    }

    /**
     * @param string[] $command The command to run and its arguments as separate
     *   string values, e.g., ['require', 'lorem/ipsum']. The return value of
     *   ::executableName() will be automatically prepended.
     * @param callable|null $callback An optional PHP callback to run whenever
     *   there is some output available on STDOUT or STDERR.
     *
     * @see https://symfony.com/doc/current/components/process.html#running-processes-asynchronously
     *
     * @throws \PhpTuf\ComposerStager\Exception\LogicException
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     */
    public function run(array $command, ?callable $callback = null): void
    {
        array_unshift($command, $this->executableName());
        $process = $this->processFactory->create($command);
        try {
            $process->mustRun($callback);
        } catch (\Symfony\Component\Process\Exception\ExceptionInterface $e) {
            throw new ProcessFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
