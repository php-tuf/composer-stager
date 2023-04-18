<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Precondition\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagerPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirIsReadyInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class StagerPreconditions extends AbstractPreconditionsTree implements StagerPreconditionsInterface
{
    public function __construct(
        CommonPreconditionsInterface $commonPreconditions,
        StagingDirIsReadyInterface $stagingDirIsReady,
    ) {
        parent::__construct(...func_get_args());
    }

    public function getName(): string
    {
        return 'Stager preconditions';
    }

    public function getDescription(): string
    {
        return 'The preconditions for staging Composer commands.';
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The preconditions for staging Composer commands are fulfilled.';
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The preconditions for staging Composer commands are unfulfilled.';
    }
}
