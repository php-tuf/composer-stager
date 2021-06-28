<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\Runner;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactoryInterface;

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
     * @var \PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactoryInterface
     */
    private $processFactory;

    public function __construct(ProcessFactoryInterface $processFactory)
    {
        $this->processFactory = $processFactory;
    }

    /**
     * @param string[] $command
     *   The command to run and its arguments as separate string values, e.g.,
     *   ['require', 'lorem/ipsum']. The return value of ::executableName() will
     *   be automatically prepended.
     * @param \PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     *
     * @see https://symfony.com/doc/current/components/process.html#running-processes-asynchronously
     *
     * @throws \PhpTuf\ComposerStager\Exception\LogicException
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     */
    public function run(array $command, ?ProcessOutputCallbackInterface $callback = null): void
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
