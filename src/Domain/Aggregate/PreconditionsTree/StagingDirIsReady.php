<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree;

use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirExistsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirIsWritableInterface;

final class StagingDirIsReady extends AbstractPreconditionsTree implements StagingDirIsReadyInterface
{
    public function __construct(
        StagingDirExistsInterface $stagingDirExists,
        StagingDirIsWritableInterface $stagingDirIsWritable
    ) {
        /** @var array<\PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface> $children */
        $children = func_get_args();

        parent::__construct(...$children);
    }

    public function getName(): string
    {
        return 'Staging directory is ready'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The preconditions for using the staging directory.'; // @codeCoverageIgnore
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The staging directory is ready to use.'; // @codeCoverageIgnore
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The staging directory is not ready to use.'; // @codeCoverageIgnore
    }
}
