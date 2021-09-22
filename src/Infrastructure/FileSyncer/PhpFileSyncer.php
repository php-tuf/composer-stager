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
        ?array $exclusions = [],
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void {
        $this->source = $source;
        $this->destination = $destination;
        $this->exclusions = array_unique((array) $exclusions);

        set_time_limit((int) $timeout);

        try {
            $this->indexDestination();
            $this->deleteExtraneousFilesFromDestination();
            $this->indexSource();
            $this->copyNewFilesToDestination();
        } catch (IOException | LogicException $e) {
            throw new ProcessFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }

        // @todo Reset Symfony Finders so they aren't polluted if this class is
        //   used again within the same PHP process.
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
            $source = DirectoryUtil::stripAncestor($this->source, $this->destination);
            $this->destinationFinder
                ->in($this->destination)
                ->notPath($this->exclusions)
                // @todo Excluding the source makes it work when the
                //   destination is inside the source, but causes it to fail
                //   when the destination is an absolute path.
                ->notPath($source);
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
        $source = DirectoryUtil::ensureTrailingSlash($this->source);

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
            $destination = DirectoryUtil::stripAncestor($this->destination, $this->source);
            $destination = DirectoryUtil::ensureTrailingSlash($destination);
            $this->sourceFinder
                ->in($this->source)
                ->notPath($this->exclusions)
                // @todo Excluding the destination makes it work when the
                //   destination is inside the source, but causes it to fail
                //   when the destination is an absolute path.
                ->notPath($destination);
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
        $destination = DirectoryUtil::ensureTrailingSlash($this->destination);

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
