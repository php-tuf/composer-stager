<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Process\Service;

use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface;
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

    private ?SymfonyProcess $symfonyProcess = null;

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
        private readonly array $command = [],
    ) {
        $this->setTranslatableFactory($translatableFactory);
    }

    public function getOutput(): string
    {
        try {
            return $this->getSymfonyProcess()->getOutput();
        } catch (Throwable $e) {
            throw new LogicException($this->t(
                'Failed to get process output: %details',
                $this->p(['%details' => $e->getMessage()]),
                $this->d()->exceptions(),
            ), 0, $e);
        }
    }

    public function mustRun(?ProcessOutputCallbackInterface $callback = null): self
    {
        try {
            $this->getSymfonyProcess()->mustRun($callback);
        } catch (Throwable $e) {
            throw new RuntimeException($this->t(
                'Failed to run process: %details',
                $this->p(['%details' => $e->getMessage()]),
                $this->d()->exceptions(),
            ), 0, $e);
        }

        return $this;
    }

    public function setTimeout(?float $timeout = self::DEFAULT_TIMEOUT): self
    {
        try {
            $this->getSymfonyProcess()->setTimeout($timeout);
        } catch (Throwable $e) {
            throw new InvalidArgumentException($this->t(
                'Failed to set process timeout: %details',
                $this->p(['%details' => $e->getMessage()]),
                $this->d()->exceptions(),
            ), 0, $e);
        }

        return $this;
    }

    /**
     * Gets the contained Symfony process if there is one or creates one if there isn't.
     *
     * This approach is taken to avoid side effects in the constructor--i.e., calling
     * the factory in it--which, of course, would complicate unit tests.
     */
    private function getSymfonyProcess(): SymfonyProcess
    {
        if (!$this->symfonyProcess instanceof SymfonyProcess) {
            $this->symfonyProcess = $this->symfonyProcessFactory->create($this->command);
        }

        return $this->symfonyProcess;
    }
}
