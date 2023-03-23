<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree;

use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\ActiveDirIsReadyInterface;
use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveAndStagingDirsAreDifferentInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ComposerIsAvailableInterface;

/** @internal Don't instantiate this class directly. Get it from the service container via its interface. */
final class CommonPreconditions extends AbstractPreconditionsTree implements CommonPreconditionsInterface
{
    public function __construct(
        ActiveAndStagingDirsAreDifferentInterface $activeAndStagingDirsAreDifferent,
        ActiveDirIsReadyInterface $activeDirIsReady,
        ComposerIsAvailableInterface $composerIsAvailable,
    ) {
        parent::__construct(...func_get_args());
    }

    public function getName(): string
    {
        return 'Common preconditions'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The preconditions common to all operations.'; // @codeCoverageIgnore
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The common preconditions are fulfilled.';
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The common preconditions are unfulfilled.';
    }
}
