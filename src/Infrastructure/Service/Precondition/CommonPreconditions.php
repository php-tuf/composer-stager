<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveAndStagingDirsAreDifferentInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirIsReadyInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ComposerIsAvailableInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
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
        return 'Common preconditions';
    }

    public function getDescription(): string
    {
        return 'The preconditions common to all operations.';
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The common preconditions are fulfilled.';
    }
}
