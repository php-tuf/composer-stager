<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Factory\Translation\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\BeginnerPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoUnsupportedLinksExistInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirDoesNotExistInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslatableInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class BeginnerPreconditions extends AbstractPreconditionsTree implements BeginnerPreconditionsInterface
{
    public function __construct(
        CommonPreconditionsInterface $commonPreconditions,
        NoUnsupportedLinksExistInterface $noUnsupportedLinksExist,
        StagingDirDoesNotExistInterface $stagingDirDoesNotExist,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        parent::__construct(
            $translatableFactory,
            $commonPreconditions,
            $noUnsupportedLinksExist,
            $stagingDirDoesNotExist,
        );
    }

    public function getName(): TranslatableInterface
    {
        return $this->t('Beginner preconditions');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('The preconditions for beginning the staging process.');
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('The preconditions for beginning the staging process are fulfilled.');
    }
}
