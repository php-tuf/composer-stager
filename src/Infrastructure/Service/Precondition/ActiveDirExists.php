<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirExistsInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class ActiveDirExists extends AbstractPrecondition implements ActiveDirExistsInterface
{
    public function __construct(private readonly FilesystemInterface $filesystem)
    {
    }

    public function getName(): string
    {
        return 'Active directory exists';
    }

    public function getDescription(): string
    {
        return 'There must be an active directory present before any operations can be performed.';
    }

    public function isFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): bool {
        return $this->filesystem->exists($activeDir);
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The active directory exists.';
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The active directory does not exist.';
    }
}
