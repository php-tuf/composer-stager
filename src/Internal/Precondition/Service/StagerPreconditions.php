<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Precondition\Service;

use PhpTuf\ComposerStager\API\Precondition\Service\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\StagerPreconditionsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\StagingDirIsReadyInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactoryInterface;

/**
 * @package Precondition
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class StagerPreconditions extends AbstractPreconditionsTree implements StagerPreconditionsInterface
{
    public function __construct(
        TranslatableFactoryInterface $translatableFactory,
        CommonPreconditionsInterface $commonPreconditions,
        StagingDirIsReadyInterface $stagingDirIsReady,
    ) {
        parent::__construct($translatableFactory, $commonPreconditions, $stagingDirIsReady);
    }

    public function getName(): TranslatableInterface
    {
        return $this->t('Stager preconditions');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('The preconditions for staging Composer commands.');
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('The preconditions for staging Composer commands are fulfilled.');
    }
}
