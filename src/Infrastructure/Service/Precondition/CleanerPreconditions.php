<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Factory\Translation\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\CleanerPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirIsReadyInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslatableInterface;

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
