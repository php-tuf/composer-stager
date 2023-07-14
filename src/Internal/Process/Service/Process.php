<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Process\Service;

use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Internal\Process\Factory\SymfonyProcessFactoryInterface;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait;
use Symfony\Component\Process\Process as SymfonyProcess;
use Throwable;

/**
 * @package Process
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class Process implements ProcessInterface
{
    use TranslatableAwareTrait;

    private readonly SymfonyProcess $symfonyProcess;

    /**
     * @param array<string> $command
     *   The command parts exactly as they would be typed in the terminal.
     *   There's no need to escape them in any way, only to separate them. Example:
     *   ```php
     *   $command = [
     *       'composer',
     *       'require',
     *       'example/package:"^1 || ^2"',
     *       '--with-all-dependencies',
     *   ];
     *   ```
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\LogicException
     *   If the process cannot be created due to host configuration.
     */
    public function __construct(
        private readonly SymfonyProcessFactoryInterface $symfonyProcessFactory,
        TranslatableFactoryInterface $translatableFactory,
        array $command = [],
    ) {
        $this->setTranslatableFactory($translatableFactory);
        $this->symfonyProcess = $this->symfonyProcessFactory->create($command);
    }

    public function getOutput(): string
    {
        try {
            return $this->symfonyProcess->getOutput();
        } catch (Throwable $e) {
            throw new LogicException($this->t(
                'Failed to get process output: %details',
                $this->p(['%details' => $e->getMessage()]),
                $this->d()->exceptions(),
            ), 0, $e);
        }
    }

    public function mustRun(?OutputCallbackInterface $callback = null): self
    {
        try {
            $callbackAdapter = new OutputCallbackAdapter($callback);
            $this->symfonyProcess->mustRun($callbackAdapter);
        } catch (Throwable $e) {
            throw new RuntimeException($this->t(
                'Failed to run process: %details',
                $this->p(['%details' => $e->getMessage()]),
                $this->d()->exceptions(),
            ), 0, $e);
        }

        return $this;
    }

    public function run(?OutputCallbackInterface $callback = null): int
    {
        $callbackAdapter = new OutputCallbackAdapter($callback);

        try {
            return $this->symfonyProcess->run($callbackAdapter);
        } catch (Throwable $e) {
            throw new RuntimeException($this->t(
                'Failed to run process: %details',
                $this->p(['%details' => $e->getMessage()]),
                $this->d()->exceptions(),
            ), 0, $e);
        }
    }

    public function setTimeout(?float $timeout = self::DEFAULT_TIMEOUT): self
    {
        try {
            $this->symfonyProcess->setTimeout($timeout);
        } catch (Throwable $e) {
            throw new InvalidArgumentException($this->t(
                'Failed to set process timeout: %details',
                $this->p(['%details' => $e->getMessage()]),
                $this->d()->exceptions(),
            ), 0, $e);
        }

        return $this;
    }
}
