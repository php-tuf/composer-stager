<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirIsWritableInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;

final class StagingDirIsWritable extends AbstractPrecondition implements StagingDirIsWritableInterface
{
    private FilesystemInterface $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getName(): string
    {
        return 'Staging directory is writable'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The staging directory must be writable before any operations can be performed.'; // @codeCoverageIgnore
    }

    public function isFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null
    ): bool {
        return $this->filesystem->isWritable($stagingDir);
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The staging directory is writable.';
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The staging directory is not writable.';
    }
}
