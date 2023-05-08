<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Precondition\BeginnerPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoUnsupportedLinksExistInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirDoesNotExistInterface;

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
    ) {
        parent::__construct(...func_get_args());
    }

    public function getName(): string
    {
        return 'Beginner preconditions';
    }

    public function getDescription(): string
    {
        return 'The preconditions for beginning the staging process.';
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The preconditions for beginning the staging process are fulfilled.';
    }
}
