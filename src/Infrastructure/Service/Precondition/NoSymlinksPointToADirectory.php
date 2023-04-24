<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoSymlinksPointToADirectoryInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface;

/**
 * @package Precondition
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class NoSymlinksPointToADirectory extends AbstractFileIteratingPrecondition implements
    NoSymlinksPointToADirectoryInterface
{
    public function __construct(
        RecursiveFileFinderInterface $fileFinder,
        private readonly FileSyncerInterface $fileSyncer,
        FilesystemInterface $filesystem,
        PathFactoryInterface $pathFactory,
    ) {
        parent::__construct($fileFinder, $filesystem, $pathFactory);
    }

    public function getName(): string
    {
        return 'No symlinks point to a directory';
    }

    public function getDescription(): string
    {
        return 'The codebase cannot contain symlinks that point to a directory.';
    }

    protected function getFulfilledStatusMessage(): string
    {
        return 'There are no symlinks that point to a directory.';
    }

    protected function getDefaultUnfulfilledStatusMessage(): string
    {
        return <<<'EOF'
The %s directory at "%s" contains symlinks that point to a directory, which is not supported. The first one is "%s".
EOF;
    }

    protected function exitEarly(
        PathInterface $activeDir,
        PathInterface $stagingDir,
        ?PathListInterface $exclusions,
    ): bool {
        // RsyncFileSyncer supports symlinks pointing to directories, but
        // PhpFileSyncer does not yet.
        // @see https://github.com/php-tuf/composer-stager/issues/99
        return $this->fileSyncer instanceof RsyncFileSyncerInterface;
    }

    protected function isSupportedFile(PathInterface $file, PathInterface $codebaseRootDir): bool
    {
        if (!$this->filesystem->isSymlink($file)) {
            return true;
        }

        $target = $this->filesystem->readLink($file);

        return !$this->filesystem->isDir($target);
    }
}
