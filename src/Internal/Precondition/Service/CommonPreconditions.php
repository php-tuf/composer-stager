<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Precondition\Service;

use PhpTuf\ComposerStager\API\Precondition\Service\ActiveAndStagingDirsAreDifferentInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\ActiveDirIsReadyInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\ComposerIsAvailableInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;

/**
 * @package Precondition
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
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
