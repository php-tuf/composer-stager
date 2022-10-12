<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirDoesNotExistInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;

final class StagingDirDoesNotExist extends AbstractPrecondition implements StagingDirDoesNotExistInterface
{
    private FilesystemInterface $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getName(): string
    {
        return 'Staging directory does not exist'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The staging directory must not already exist before beginning the staging process.'; // @codeCoverageIgnore
    }

    public function isFulfilled(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions = null
    ): bool {
        return !$this->filesystem->exists($stagingDir);
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The staging directory does not already exist.'; // @codeCoverageIgnore
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The staging directory already exists.'; // @codeCoverageIgnore
    }
}
