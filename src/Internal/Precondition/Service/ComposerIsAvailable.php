<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Precondition\Service;

use JsonException as PhpJsonException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\ComposerIsAvailableInterface;
use PhpTuf\ComposerStager\API\Process\Factory\ProcessFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use Symfony\Component\Process\Exception\LogicException as SymfonyLogicException;
use Symfony\Component\Process\Exception\ProcessFailedException as SymfonyProcessFailedException;
use Symfony\Component\Process\Process as SymfonyProcess;

/**
 * @package Precondition
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class ComposerIsAvailable extends AbstractPrecondition implements ComposerIsAvailableInterface
{
    private string $executablePath;

    public function __construct(
        private readonly ExecutableFinderInterface $executableFinder,
        private readonly ProcessFactoryInterface $processFactory,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        parent::__construct($translatableFactory);
    }

    public function getName(): TranslatableInterface
    {
        return $this->t('Composer');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('Composer must be available in order to stage commands.');
    }

    public function assertIsFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): void {
        $this->assertExecutableExists();
        $this->assertIsActuallyComposer();
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('Composer is available.');
    }

    /** @throws \PhpTuf\ComposerStager\API\Exception\PreconditionException */
    private function assertExecutableExists(): void
    {
        try {
            $this->executablePath = $this->executableFinder->find('composer');
        } catch (LogicException $e) {
            throw new PreconditionException($this, $this->t(
                'Cannot find Composer.',
                null,
                $this->d()->exceptions(),
            ), 0, $e);
        }
    }

    /** @throws \PhpTuf\ComposerStager\API\Exception\PreconditionException */
    private function assertIsActuallyComposer(): void
    {
        $process = $this->getProcess();

        if (!$this->isValidExecutable($process)) {
            throw new PreconditionException($this, $this->t(
                'The Composer executable at %name is invalid.',
                $this->p(['%name' => $this->executablePath]),
                $this->d()->exceptions(),
            ));
        }
    }

    /** @throws \PhpTuf\ComposerStager\API\Exception\PreconditionException */
    private function getProcess(): SymfonyProcess
    {
        try {
            return $this->processFactory->create([
                $this->executablePath,
                'list',
                '--format=json',
            ]);
        } catch (LogicException $e) {
            throw new PreconditionException($this, $this->t(
                'Cannot check for Composer due to a host configuration problem: %details',
                $this->p(['%details' => $e->getMessage()]),
                $this->d()->exceptions(),
            ), 0, $e);
        }
    }

    private function isValidExecutable(SymfonyProcess $process): bool
    {
        try {
            $process->mustRun();
            $output = $process->getOutput();
        } catch (SymfonyLogicException|SymfonyProcessFailedException) {
            return false;
        }

        try {
            $data = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        } catch (PhpJsonException) {
            return false;
        }

        if (!isset($data['application']['name'])) {
            return false;
        }

        return $data['application']['name'] === 'Composer';
    }
}
