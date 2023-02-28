<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirIsWritableInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;

final class ActiveDirIsWritable extends AbstractPrecondition implements ActiveDirIsWritableInterface
{
    public function __construct(private readonly FilesystemInterface $filesystem)
    {
    }

    public function getName(): string
    {
        return 'Active directory is writable'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The active directory must be writable before any operations can be performed.'; // @codeCoverageIgnore
    }

    public function isFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null,
    ): bool {
        return $this->filesystem->isWritable($activeDir);
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The active directory is writable.';
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The active directory is not writable.';
    }
}
