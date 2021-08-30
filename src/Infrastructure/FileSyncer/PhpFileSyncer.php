<?php

namespace PhpTuf\ComposerStager\Infrastructure\FileSyncer;

use LogicException;
use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Util\DirectoryUtil;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
final class PhpFileSyncer implements PhpFileSyncerInterface
{
    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface
     */
    private $filesystem;

    /**
     * @var \Symfony\Component\Finder\Finder
     */
    private $sourceFinder;

    /**
     * @var \Symfony\Component\Finder\Finder
     */
    private $destinationFinder;

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
        ?array $exclusions = [],
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void {
        $source = DirectoryUtil::stripTrailingSlash($source);
        $destination = DirectoryUtil::stripTrailingSlash($destination);

        set_time_limit((int) $timeout);

        try {
            $this->mirror($source, $destination, (array) $exclusions);
        } catch (IOException | LogicException $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string[] $exclusions
     *
     * @todo This whole method (and the rest of the class) could possibly be replaced with
     *   a simple call to \Symfony\Component\Filesystem\Filesystem::mirror, but that
     *   method has a bug affecting our use case. Consider using it instead once it's
     *   fixed. Just be sure it has feature-parity with our path-handling--particularly,
     *   support for an empty string as a relative path. See the unit tests.
     * @see \Symfony\Component\Filesystem\Filesystem::mirror
     * @see https://github.com/symfony/symfony/issues/14068
     *
     * @throws \LogicException
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     */
    private function mirror(string $source, string $destination, array $exclusions = []): void
    {
        $this->indexDirectories($source, $destination, $exclusions);
        $this->deleteExtraneousFilesFromDestination($source, $destination);
        $this->copyNewFilesToDestination($source, $destination);
    }

    /**
     * @param string[] $exclusions
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     */
    private function indexDirectories(string $source, string $destination, array $exclusions): void
    {
        // Index the source directory.
        try {
            $this->sourceFinder
                ->in($source)
                ->notPath($exclusions);
        } catch (\Symfony\Component\Finder\Exception\DirectoryNotFoundException $e) {
            throw new DirectoryNotFoundException(
                $source,
                'The source directory does not exist at "%s"',
                (int) $e->getCode(),
                $e
            );
        }

        // Index the destination.
        try {
            // Ensure the destination directory's existence. (This has no effect
            // if it already exists.)
            $this->filesystem->mkdir($destination);
            // Index it.
            $this->destinationFinder
                ->in($destination)
                ->notPath($exclusions);
        } catch (\Symfony\Component\Finder\Exception\DirectoryNotFoundException | IOException $e) {
            $message = sprintf('The destination directory could not be created at "%s".', $destination);
            throw new ProcessFailedException($message, (int) $e->getCode(), $e);
        }
    }

    /**
     * @throws \LogicException
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     */
    private function deleteExtraneousFilesFromDestination(string $source, string $destination): void
    {
        $source = DirectoryUtil::ensureTrailingSlash($source);

        /** @var \Symfony\Component\Finder\SplFileInfo $destinationFileInfo */
        foreach ($this->destinationFinder as $destinationFileInfo) {
            $relativePathname = DirectoryUtil::getDescendantRelativeToAncestor($destination, $destinationFileInfo->getPathname());

            $sourcePathname = $source . $relativePathname;

            if (!$this->filesystem->exists($sourcePathname)) {
                $this->filesystem->remove($destinationFileInfo->getPathname());
            }
        }
    }

    /**
     * @throws \LogicException
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function copyNewFilesToDestination(string $source, string $destination): void
    {
        $destination = DirectoryUtil::ensureTrailingSlash($destination);

        /** @var \Symfony\Component\Finder\SplFileInfo $sourceFileInfo */
        foreach ($this->sourceFinder as $sourceFileInfo) {
            $sourcePathname = $sourceFileInfo->getPathname();

            $relativePathname = DirectoryUtil::getDescendantRelativeToAncestor($source, $sourcePathname);
            $destinationPath = $destination . $relativePathname;

            // Note: Symlinks will be treated as the paths they point to.
            if ($this->filesystem->isDir($sourcePathname)) {
                $this->filesystem->mkdir($destinationPath);
            } elseif ($this->filesystem->isFile($sourcePathname)) {
                $this->filesystem->copy($sourcePathname, $destinationPath);
            } else {
                throw new IOException(
                    sprintf('Unable to determine file type of "%s".', $sourcePathname)
                );
            }
        }
    }
}
