<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\ProcessRunner;

use PhpTuf\ComposerStager\Domain\Exception\RuntimeException;
use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\ProcessRunnerInterface;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Process\ProcessFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinderInterface;
use Symfony\Component\Process\Exception\ExceptionInterface as SymfonyExceptionInterface;

/**
 * Provides a base for process runners for consistent process creation and
 * exception-handling.
 *
 * @package ProcessRunner
 *
 * @api
 */
abstract class AbstractRunner implements ProcessRunnerInterface
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
     * @param \PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\LogicException
     *   If the command process cannot be created due to host configuration.
     * @throws \PhpTuf\ComposerStager\Domain\Exception\RuntimeException
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

        try {
            $process->setTimeout($timeout);
            $process->mustRun($callback);
        } catch (SymfonyExceptionInterface $e) {
            throw new RuntimeException($this->t($e->getMessage()), 0, $e);
        }
    }

    /** @throws \PhpTuf\ComposerStager\Domain\Exception\LogicException */
    private function findExecutable(): string
    {
        $name = $this->executableName();

        return $this->executableFinder->find($name);
    }
}
