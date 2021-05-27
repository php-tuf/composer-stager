<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process;

use PhpTuf\ComposerStager\Exception\ProcessFailedException;

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
     *   ::executableName() will be automatically prefixed.
     * @param callable|null $callback An optional PHP callback to run whenever
     *   there is some output available on STDOUT or STDERR.
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
        } catch (\Symfony\Component\Process\Exception\ProcessFailedException $e) {
            throw new ProcessFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
