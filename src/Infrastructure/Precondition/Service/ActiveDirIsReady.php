<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Precondition\Service;

use PhpTuf\ComposerStager\Domain\Precondition\Service\ActiveDirExistsInterface;
use PhpTuf\ComposerStager\Domain\Precondition\Service\ActiveDirIsReadyInterface;
use PhpTuf\ComposerStager\Domain\Precondition\Service\ActiveDirIsWritableInterface;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Translation\Value\TranslatableInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class ActiveDirIsReady extends AbstractPreconditionsTree implements ActiveDirIsReadyInterface
{
    public function __construct(
        ActiveDirExistsInterface $activeDirExists,
        ActiveDirIsWritableInterface $activeDirIsWritable,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        parent::__construct($translatableFactory, $activeDirExists, $activeDirIsWritable);
    }

    public function getName(): TranslatableInterface
    {
        return $this->t('Active directory is ready');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('The preconditions for using the active directory.');
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('The active directory is ready to use.');
    }
}
