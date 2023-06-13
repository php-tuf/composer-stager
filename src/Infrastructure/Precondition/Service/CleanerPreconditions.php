<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Precondition\Service;

use PhpTuf\ComposerStager\API\Precondition\Service\CleanerPreconditionsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\StagingDirIsReadyInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class CleanerPreconditions extends AbstractPreconditionsTree implements CleanerPreconditionsInterface
{
    public function __construct(
        CommonPreconditionsInterface $commonPreconditions,
        StagingDirIsReadyInterface $stagingDirIsReady,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        parent::__construct($translatableFactory, $commonPreconditions, $stagingDirIsReady);
    }

    public function getName(): TranslatableInterface
    {
        return $this->t('Cleaner preconditions');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('The preconditions for removing the staging directory.');
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('The preconditions for removing the staging directory are fulfilled.');
    }
}
