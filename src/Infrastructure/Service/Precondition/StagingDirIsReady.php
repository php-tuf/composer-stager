<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirExistsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirIsReadyInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirIsWritableInterface;

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
    ) {
        parent::__construct(...func_get_args());
    }

    public function getName(): string
    {
        return 'Staging directory is ready';
    }

    public function getDescription(): string
    {
        return 'The preconditions for using the staging directory.';
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The staging directory is ready to use.';
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The staging directory is not ready to use.';
    }
}
