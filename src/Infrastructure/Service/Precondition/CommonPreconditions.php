<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Factory\Translation\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveAndStagingDirsAreDifferentInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirIsReadyInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ComposerIsAvailableInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslatableInterface;

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
        TranslatableFactoryInterface $translatableFactory,
    ) {
        parent::__construct(
            $translatableFactory,
            $activeAndStagingDirsAreDifferent,
            $activeDirIsReady,
            $composerIsAvailable,
        );
    }

    public function getName(): TranslatableInterface
    {
        return $this->t('Common preconditions');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('The preconditions common to all operations.');
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('The common preconditions are fulfilled.');
    }
}
