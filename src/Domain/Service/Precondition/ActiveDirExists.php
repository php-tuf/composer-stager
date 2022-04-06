<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class ActiveDirExists extends AbstractPrecondition implements ActiveDirExistsInterface
{
    /** @var \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface */
    private $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getName(): string
    {
        return 'Active directory exists'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'There must be an active directory present before any operations can be performed.'; // @codeCoverageIgnore
    }

    public function isFulfilled(PathInterface $activeDir, PathInterface $stagingDir): bool
    {
        return $this->filesystem->exists($activeDir->resolve());
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The active directory exists.'; // @codeCoverageIgnore
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The active directory does not exist.'; // @codeCoverageIgnore
    }
}
