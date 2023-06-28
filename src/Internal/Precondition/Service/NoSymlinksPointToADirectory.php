<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\API\FileSyncer\Service\RsyncFileSyncerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\NoSymlinksPointToADirectoryInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Finder\Service\FileFinderInterface;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactoryInterface;

/**
 * @package Precondition
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class NoSymlinksPointToADirectory extends AbstractFileIteratingPrecondition implements
    NoSymlinksPointToADirectoryInterface
{
    public function __construct(
        FileFinderInterface $fileFinder,
        private readonly FileSyncerInterface $fileSyncer,
        FilesystemInterface $filesystem,
        PathFactoryInterface $pathFactory,
        TranslatableFactoryInterface $translatableFactory,
    ) {
        parent::__construct($fileFinder, $filesystem, $pathFactory, $translatableFactory);
    }

    public function getName(): TranslatableInterface
    {
        return $this->t('No symlinks point to a directory');
    }

    public function getDescription(): TranslatableInterface
    {
        return $this->t('The codebase cannot contain symlinks that point to a directory.');
    }

    protected function getFulfilledStatusMessage(): TranslatableInterface
    {
        return $this->t('There are no symlinks that point to a directory.');
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

    protected function assertIsSupportedFile(
        string $codebaseName,
        PathInterface $codebaseRoot,
        PathInterface $file,
    ): void {
        if (!$this->filesystem->isSymlink($file)) {
            return;
        }

        $target = $this->filesystem->readLink($file);

        if ($this->filesystem->isDir($target)) {
            throw new PreconditionException(
                $this,
                $this->t(
                    'The %codebase_name directory at %codebase_root contains symlinks that point to a directory, '
                    . 'which is not supported. The first one is %file.',
                    $this->p([
                        '%codebase_name' => $codebaseName,
                        '%codebase_root' => $codebaseRoot->resolved(),
                        '%file' => $file->resolved(),
                    ]),
                    $this->d()->exceptions(),
                ),
            );
        }
    }
}
