<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

final class StagingDirExists extends AbstractPrecondition implements StagingDirExistsInterface
{
    /** @var \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface */
    private $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;

        parent::__construct();
    }

    public static function getName(): string
    {
        return 'Staging directory exists'; // @codeCoverageIgnore
    }

    public static function getDescription(): string
    {
        return 'The staging directory must exist before any operations can be performed.'; // @codeCoverageIgnore
    }

    public function isFulfilled(PathInterface $activeDir, PathInterface $stagingDir): bool
    {
        return $this->filesystem->exists($stagingDir->resolve());
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
