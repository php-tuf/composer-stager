<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class ActiveDirIsWritable extends AbstractPrecondition implements ActiveDirIsWritableInterface
{
    /** @var \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface */
    private $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;

        parent::__construct();
    }

    public function getName(): string
    {
        return 'Active directory is writable'; // @codeCoverageIgnore
    }

    public function getDescription(): string
    {
        return 'The active directory must be writable before any operations can be performed.'; // @codeCoverageIgnore
    }

    public function isFulfilled(PathInterface $activeDir, PathInterface $stagingDir): bool
    {
        return $this->filesystem->isWritable($activeDir->resolve());
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'The active directory is writable.'; // @codeCoverageIgnore
    }

    protected function getUnfulfilledStatusMessage(): string
    {
        return 'The active directory is not writable.'; // @codeCoverageIgnore
    }
}
