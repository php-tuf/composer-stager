<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

final class CleanerPreconditions extends AbstractPrecondition implements CleanerPreconditionsInterface
{
    public function __construct(
        CommonPreconditionsInterface $commonPreconditions,
        StagingDirIsReadyInterface $stagingDirIsReady
    ) {
        /** @var array<\PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface> $children */
        $children = func_get_args();

        parent::__construct(...$children);
    }

    public function getName(): string
    {
        return 'Cleaner preconditions'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The preconditions for removing the staging directory.'; // @codeCoverageIgnore
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The preconditions for removing the staging directory are fulfilled.'; // @codeCoverageIgnore
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The preconditions for removing the staging directory are unfulfilled.'; // @codeCoverageIgnore
    }
}
