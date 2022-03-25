<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

final class BeginnerPreconditions extends AbstractPrecondition implements BeginnerPreconditionsInterface
{
    public function __construct(
        CommonPreconditionsInterface $commonPreconditions,
        StagingDirDoesNotExistInterface $stagingDirDoesNotExist
    ) {
        /** @var array<\PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface> $children */
        $children = func_get_args();

        parent::__construct(...$children);
    }

    public function getName(): string
    {
        return 'Beginner preconditions'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The preconditions for beginning the staging process.'; // @codeCoverageIgnore
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The preconditions for beginning the staging process are fulfilled.'; // @codeCoverageIgnore
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The preconditions for beginning the staging process are unfulfilled.'; // @codeCoverageIgnore
    }
}
