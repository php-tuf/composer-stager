<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Precondition\Service;

use PhpTuf\ComposerStager\Domain\Precondition\Service\ActiveAndStagingDirsAreDifferentInterface;
use PhpTuf\ComposerStager\Domain\Precondition\Service\ActiveDirIsReadyInterface;
use PhpTuf\ComposerStager\Domain\Precondition\Service\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Precondition\Service\ComposerIsAvailableInterface;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Translation\Value\TranslatableInterface;

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
