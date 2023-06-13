<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Precondition\Service;

use PhpTuf\ComposerStager\API\Precondition\Service\StagingDirExistsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\StagingDirIsReadyInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\StagingDirIsWritableInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class StagingDirIsReady extends AbstractPreconditionsTree implements StagingDirIsReadyInterface
{
    public function __construct(
        StagingDirExistsInterface $stagingDirExists,
        StagingDirIsWritableInterface $stagingDirIsWritable,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        parent::__construct($translatableFactory, $stagingDirExists, $stagingDirIsWritable);
    }

    public function getName(): TranslatableInterface
    {
        return $this->t('Staging directory is ready');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('The preconditions for using the staging directory.');
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('The staging directory is ready to use.');
    }
}
