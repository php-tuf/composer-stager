<?php

namespace PhpTuf\ComposerStager\Infrastructure\FileSyncer;

use LogicException;
use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Util\PathUtil;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
final class PhpFileSyncer implements FileSyncerInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface
     */
    private $filesystem;

    /**
     * @var string
     */
    private $source = '';

    /**
     * @var \Symfony\Component\Finder\Finder
     */
    private $sourceFinder;

    /**
     * @var string
     */
    private $destination = '';

    /**
     * @var \Symfony\Component\Finder\Finder
     */
    private $destinationFinder;

    /**
     * @var string[]
     */
    private $exclusions = [];

    public function __construct(
        FilesystemInterface $filesystem,
        Finder $sourceFinder,
        Finder $destinationFinder
    ) {
        $this->filesystem = $filesystem;

        // Injected Finders must always be cloned to avoid reusing and polluting
        // the same instances.
        $this->sourceFinder = clone $sourceFinder;
        $this->destinationFinder = clone $destinationFinder;
    }

    public function sync(
        string $source,
        string $destination,
        array $exclusions = [],
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void {
        $this->source = $source;
        $this->destination = $destination;
        $this->exclusions = array_unique($exclusions);

        set_time_limit((int) $timeout);

        try {
            $this->indexDestination();
            $this->deleteExtraneousFilesFromDestination();
            $this->indexSource();
            $this->copyNewFilesToDestination();
        } catch (IOException | LogicException $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     */
    private function indexDestination(): void
    {
        try {
            // Ensure the destination directory's existence. (This has no effect
            // if it already exists.)
            $this->filesystem->mkdir($this->destination);

            // Index it.
            $source = PathUtil::getPathRelativeToAncestor($this->source, $this->destination);
            $source = PathUtil::ensureTrailingSlash($source);
            $this->destinationFinder
                ->in($this->destination)
                ->notPath($this->exclusions);
            // Exclude the source directory in order to prevent a Finder
            // AccessDeniedException if it is an ancestor of the destination
            // directory (i.e., if it is "underneath" or "inside" it)--unless it
            // is a UNIX-like absolute path, which triggers a bug in Finder.
            // @see https://github.com/symfony/symfony/issues/43282
            if ($source[0] !== '/') {
                $this->destinationFinder->notPath($source);
            }
        } catch (\Symfony\Component\Finder\Exception\DirectoryNotFoundException | IOException $e) {
            throw new ProcessFailedException(sprintf(
                'The destination directory could not be created at "%s".',
                $this->destination
            ), (int) $e->getCode(), $e);
        }
    }

    /**
     * @throws \LogicException
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     */
    private function deleteExtraneousFilesFromDestination(): void
    {
        $source = PathUtil::ensureTrailingSlash($this->source);

        /** @var \Symfony\Component\Finder\SplFileInfo $destinationFileInfo */
        foreach ($this->destinationFinder as $destinationFileInfo) {
            $destinationPathname = $destinationFileInfo->getPathname();
            $sourcePathname = $source . $destinationFileInfo->getRelativePathname();

            if (!$this->filesystem->exists($sourcePathname)) {
                $this->filesystem->remove($destinationPathname);
            }
        }
    }

    /**
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     */
    private function indexSource(): void
    {
        try {
            $destination = PathUtil::getPathRelativeToAncestor($this->destination, $this->source);
            $destination = PathUtil::ensureTrailingSlash($destination);
            $this->sourceFinder
                ->in($this->source)
                ->notPath($this->exclusions);
            // Exclude the destination directory in order to prevent infinite
            // recursion if it is a descendant of the source directory (i.e., if
            // it is "underneath" or "inside" it)--unless it is a UNIX-like
            // absolute path, which triggers a bug in Finder.
            // @see https://github.com/symfony/symfony/issues/43282
            if ($destination[0] !== '/') {
                $this->sourceFinder->notPath($destination);
            }
        } catch (\Symfony\Component\Finder\Exception\DirectoryNotFoundException $e) {
            throw new DirectoryNotFoundException(
                $this->source,
                'The source directory does not exist at "%s"',
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * @throws \LogicException
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function copyNewFilesToDestination(): void
    {
        $destination = PathUtil::ensureTrailingSlash($this->destination);

        /** @var \Symfony\Component\Finder\SplFileInfo $sourceFileInfo */
        foreach ($this->sourceFinder as $sourceFileInfo) {
            $sourcePathname = $sourceFileInfo->getPathname();
            $destinationPathname = $destination . $sourceFileInfo->getRelativePathname();

            // Note: Symlinks will be interpreted as the paths they point to.
            if ($this->filesystem->isDir($sourcePathname)) {
                $this->filesystem->mkdir($destinationPathname);
            } elseif ($this->filesystem->isFile($sourcePathname)) {
                $this->filesystem->copy($sourcePathname, $destinationPathname);
            } else {
                throw new IOException(
                    sprintf('Unable to determine file type of "%s".', $sourcePathname)
                );
            }
        }
    }
}
