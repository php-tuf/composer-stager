<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use JsonException;
use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ComposerIsAvailableInterface;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\Domain\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Infrastructure\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Process\Factory\ProcessFactoryInterface;
use Symfony\Component\Process\Exception\LogicException as SymfonyLogicException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class ComposerIsAvailable extends AbstractPrecondition implements ComposerIsAvailableInterface
{
    private string $executablePath;

    public function __construct(
        private readonly ExecutableFinderInterface $executableFinder,
        private readonly ProcessFactoryInterface $processFactory,
        TranslatableFactoryInterface $translatableFactory,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translatableFactory, $translator);
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

    /** @throws \PhpTuf\ComposerStager\Domain\Exception\PreconditionException */
    private function assertExecutableExists(): void
    {
        try {
            $this->executablePath = $this->executableFinder->find('composer');
        } catch (LogicException $e) {
            throw new PreconditionException($this, $this->t('Cannot find Composer.'), 0, $e);
        }
    }

    /** @throws \PhpTuf\ComposerStager\Domain\Exception\PreconditionException */
    private function assertIsActuallyComposer(): void
    {
        $process = $this->getProcess();

        if (!$this->isValidExecutable($process)) {
            throw new PreconditionException($this, $this->t(
                'The Composer executable at %name is invalid.',
                $this->p(['%name' => $this->executablePath]),
            ));
        }
    }

    /** @throws \PhpTuf\ComposerStager\Domain\Exception\PreconditionException */
    private function getProcess(): Process
    {
        try {
            return $this->processFactory->create([
                $this->executablePath,
                'list',
                '--format=json',
            ]);
        } catch (LogicException $e) {
            throw new PreconditionException(
                $this,
                $this->t(
                    'Cannot check for Composer due to a host configuration problem: %problem',
                    $this->p(['%problem' => $e->getMessage()]),
                ),
                0,
                $e,
            );
        }
    }

    private function isValidExecutable(Process $process): bool
    {
        try {
            $process->mustRun();
            $output = $process->getOutput();
        } catch (SymfonyLogicException|ProcessFailedException) {
            return false;
        }

        try {
            $data = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        if (!isset($data['application']['name'])) {
            return false;
        }

        return $data['application']['name'] === 'Composer';
    }
}
