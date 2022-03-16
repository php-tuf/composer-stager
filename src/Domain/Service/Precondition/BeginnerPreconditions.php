<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class BeginnerPreconditions extends AbstractPrecondition implements BeginnerPreconditionsInterface
{
    public static function getName(): string
    {
        return 'Beginner preconditions'; // @codeCoverageIgnore
    }

    public static function getDescription(): string
    {
        return 'The preconditions for beginning the staging process.'; // @codeCoverageIgnore
    }

    public function isFulfilled(PathInterface $activeDir, PathInterface $stagingDir): bool
    {
        return true; // @codeCoverageIgnore
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
