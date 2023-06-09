<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Factory\Translation\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\CommitterPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoUnsupportedLinksExistInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirIsReadyInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslatableInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class CommitterPreconditions extends AbstractPreconditionsTree implements CommitterPreconditionsInterface
{
    public function __construct(
        CommonPreconditionsInterface $commonPreconditions,
        NoUnsupportedLinksExistInterface $noUnsupportedLinksExist,
        StagingDirIsReadyInterface $stagingDirIsReady,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        parent::__construct($translatableFactory, $commonPreconditions, $noUnsupportedLinksExist, $stagingDirIsReady);
    }

    public function getName(): TranslatableInterface
    {
        return $this->t('Committer preconditions');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('The preconditions for making staged changes live.');
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('The preconditions for making staged changes live are fulfilled.');
    }
}
