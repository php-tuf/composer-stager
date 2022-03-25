<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

final class CommonPreconditions extends AbstractPrecondition implements CommonPreconditionsInterface
{
    public function __construct(
        ComposerIsAvailableInterface $composerIsAvailable,
        ActiveDirExistsInterface $activeDirExists,
        ActiveDirIsWritableInterface $activeDirIsWritable,
        ActiveAndStagingDirsAreDifferentInterface $activeAndStagingDirsAreDifferent
    ) {
        /** @var array<\PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface> $children */
        $children = func_get_args();

        parent::__construct(...$children);
    }

    public function getName(): string
    {
        return 'Common preconditions'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The preconditions common to all operations.'; // @codeCoverageIgnore
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The common preconditions are fulfilled.'; // @codeCoverageIgnore
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The common preconditions are unfulfilled.'; // @codeCoverageIgnore
    }
}
