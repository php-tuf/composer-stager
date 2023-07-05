<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\HostSupportsRunningProcessesInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Internal\Process\Factory\ProcessFactoryInterface;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactoryInterface;

/**
 * @package Precondition
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class HostSupportsRunningProcesses extends AbstractPrecondition implements HostSupportsRunningProcessesInterface
{
    public function __construct(
        private readonly ProcessFactoryInterface $processFactory,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        parent::__construct($translatableFactory);
    }

    public function getName(): TranslatableInterface
    {
        return $this->t('Host supports running processes');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('The host must support running independent '
            . 'PHP processes in order to run Composer and other shell commands.');
    }

    public function assertIsFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): void {
        try {
            $this->processFactory->create([]);
        } catch (ExceptionInterface $e) {
            throw new PreconditionException($this, $this->t(
                'The host does not support running independent PHP processes: %details',
                $this->p(['%details' => $e->getMessage()]),
                $this->d()->exceptions(),
            ), 0, $e);
        }
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('The host supports running independent PHP processes.');
    }
}
