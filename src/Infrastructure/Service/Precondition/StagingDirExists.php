<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirExistsInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class StagingDirExists extends AbstractPrecondition implements StagingDirExistsInterface
{
    private FilesystemInterface $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getName(): string
    {
        return 'Staging directory exists'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The staging directory must exist before any operations can be performed.'; // @codeCoverageIgnore
    }

    public function isFulfilled(PathInterface $activeDir, PathInterface $stagingDir): bool
    {
        return $this->filesystem->exists($stagingDir);
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The staging directory exists.'; // @codeCoverageIgnore
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The staging directory does not exist.'; // @codeCoverageIgnore
    }
}
