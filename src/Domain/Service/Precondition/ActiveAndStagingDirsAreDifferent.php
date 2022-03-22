<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

/** phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong */
final class ActiveAndStagingDirsAreDifferent extends AbstractPrecondition implements ActiveAndStagingDirsAreDifferentInterface
{
    public static function getName(): string
    {
        return 'Active and staging directories are different'; // @codeCoverageIgnore
    }

    public static function getDescription(): string
    {
        return 'The active and staging directories cannot be the same.'; // @codeCoverageIgnore
    }

    public function isFulfilled(PathInterface $activeDir, PathInterface $stagingDir): bool
    {
        return $activeDir->resolve() !== $stagingDir->resolve();
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The active and staging directories are different.'; // @codeCoverageIgnore
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The active and staging directories are the same.'; // @codeCoverageIgnore
    }
}
