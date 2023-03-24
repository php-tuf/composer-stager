<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use JsonException;
use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ComposerIsAvailableInterface;
use PhpTuf\ComposerStager\Domain\Service\Translation\TranslationInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Process\ProcessFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinderInterface;
use Symfony\Component\Process\Exception\LogicException as SymfonyLogicException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/** @internal Don't instantiate this class directly. Get it from the service container via its interface. */
final class ComposerIsAvailable extends AbstractPrecondition implements ComposerIsAvailableInterface
{
    private string $unfulfilledStatusMessage = '';

    public function __construct(
        private readonly ExecutableFinderInterface $executableFinder,
        private readonly ProcessFactoryInterface $processFactory,
    ) {
    }

    public function getName(): string
    {
        return 'Composer'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'Composer must be available in order to stage commands.'; // @codeCoverageIgnore
    }

    public function isFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        TranslationInterface $translation,
        ?PathListInterface $exclusions = null,
    ): bool {
        try {
            $executablePath = $this->executableFinder->find('composer');
        } catch (LogicException) {
            $this->unfulfilledStatusMessage = 'Composer cannot be found.';

            return false;
        }

        try {
            $process = $this->processFactory->create([
                $executablePath,
                'list',
                '--format=json',
            ]);
        } catch (LogicException $e) {
            $this->unfulfilledStatusMessage = sprintf(
                'Cannot check for Composer due to a host configuration problem: %s',
                $e->getMessage(),
            );

            return false;
        }

        $isValid = $this->isValidExecutable($process, $executablePath);

        if (!$isValid) {
            return false;
        }

        return true;
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'Composer is available.';
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return $this->unfulfilledStatusMessage;
    }

    private function isValidExecutable(Process $process, string $executablePath): bool
    {
        $this->unfulfilledStatusMessage = sprintf('The Composer executable at %s is invalid.', $executablePath);

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
