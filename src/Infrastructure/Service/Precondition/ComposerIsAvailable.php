<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ComposerIsAvailableInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinderInterface;

final class ComposerIsAvailable extends AbstractPrecondition implements ComposerIsAvailableInterface
{
    private ExecutableFinderInterface $executableFinder;

    public function __construct(ExecutableFinderInterface $executableFinder)
    {
        $this->executableFinder = $executableFinder;
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
        ?PathListInterface $exclusions = null
    ): bool {
        try {
            $this->executableFinder->find('composer');
        } catch (LogicException $e) {
            return false;
        }

        return true;
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'Composer is available.'; // @codeCoverageIgnore
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'Composer cannot be found.'; // @codeCoverageIgnore
    }
}
