<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Process\Service;

use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\API\Process\Factory\ProcessFactoryInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessRunnerInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait;

/**
 * Provides a base for process runners for consistent process creation and
 * exception-handling.
 *
 * @package Process
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
abstract class AbstractProcessRunner implements ProcessRunnerInterface
{
    use TranslatableAwareTrait;

    /** Returns the executable name, e.g., "composer" or "rsync". */
    abstract protected function executableName(): string;

    public function __construct(
        private readonly ExecutableFinderInterface $executableFinder,
        private readonly ProcessFactoryInterface $processFactory,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        $this->setTranslatableFactory($translatableFactory);
    }

    /**
     * @param array<string> $command
     *   The command to run and its arguments as separate string values, e.g.,
     *   ['require', 'example/package'] or ['source', 'destination']. The return
     *   value of ::executableName() will be automatically prepended.
     * @param \PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\InvalidArgumentException
     *   If the given timeout is negative.
     * @throws \PhpTuf\ComposerStager\API\Exception\LogicException
     *   If the command process cannot be created due to host configuration.
     * @throws \PhpTuf\ComposerStager\API\Exception\RuntimeException
     *   If the operation fails.
     *
     * @see https://symfony.com/doc/current/components/process.html#running-processes-asynchronously
     */
    public function run(
        array $command,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = self::DEFAULT_TIMEOUT,
    ): void {
        array_unshift($command, $this->findExecutable());
        $process = $this->processFactory->create($command);
        $process->setTimeout($timeout);
        $process->mustRun($callback);
    }

    /** @throws \PhpTuf\ComposerStager\API\Exception\LogicException */
    private function findExecutable(): string
    {
        $name = $this->executableName();

        return $this->executableFinder->find($name);
    }
}
